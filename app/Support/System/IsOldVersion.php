<?php

declare(strict_types=1);

/*
 * IsOldVersion.php
 * Copyright (c) 2025 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace FireflyIII\Support\System;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Support\Facades\AppConfiguration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait IsOldVersion
{

    /**
     * Check if the tables are created and accounted for.
     *
     * @throws FireflyException
     */
    private function hasNoTables(): bool
    {
        // Log::debug('Now in routine hasNoTables()');

        try {
            DB::table('users')->count();
        } catch (QueryException $e) {
            $message = $e->getMessage();
            Log::error(sprintf('Error message trying to access users-table: %s', $message));
            if ($this->isAccessDenied($message)) {
                throw new FireflyException(
                    'It seems your database configuration is not correct. Please verify the username and password in your .env file.',
                    0,
                    $e
                );
            }
            if ($this->noTablesExist($message)) {
                // redirect to UpdateController
                Log::warning('There are no Firefly III tables present. Redirect to migrate routine.');

                return true;
            }

            throw new FireflyException(sprintf('Could not access the database: %s', $message), 0, $e);
        }

        // Log::debug('Everything seems OK with the tables.');

        return false;
    }
    /**
     * Is no tables exist error.
     */
    protected function noTablesExist(string $message): bool
    {
        return false !== stripos($message, 'Base table or view not found');
    }


    /**
     * By default, version_compare() returns -1 if the first version is lower than the second, 0 if they are equal, and
     * 1 if the second is lower.
     */
    protected function compareDevelopVersions(string $current, string $latest): int
    {
        $currentParts = explode('/', $current);
        $latestParts  = explode('/', $latest);
        if (2 !== count($currentParts) || 2 !== count($latestParts)) {
            Log::error(sprintf('Version "%s" or "%s" is not a valid develop-version.', $current, $latest));

            return 0;
        }

        $currentDate  = Carbon::createFromFormat('!Y-m-d', $currentParts[1]);
        $latestDate   = Carbon::createFromFormat('!Y-m-d', $latestParts[1]);

        if ($currentDate->lt($latestDate)) {
            Log::debug(sprintf('This current version is older, current = %s, latest version %s.', $current, $latest));

            return -1;
        }
        if ($currentDate->gt($latestDate)) {
            Log::debug(sprintf('This current version is newer, current = %s, latest version %s.', $current, $latest));

            return 1;
        }
        Log::debug(sprintf('This current version is of the same age, current = %s, latest version %s.', $current, $latest));

        return 0;
    }

    /**
     * Check if the "firefly_version" variable is correct.
     */
    protected function isOldVersionInstalled(): bool
    {
        // version compare thing.
        $configBuildTime = (int) config('firefly.build_time');
        $dbBuildTime     = (int) AppConfiguration::getFresh('ff3_build_time', 123)->data;
        $configTime      = Carbon::createFromTimestamp($configBuildTime, config('app.timezone'));
        $dbTime          = Carbon::createFromTimestamp($dbBuildTime, config('app.timezone'));
        if ($dbBuildTime < $configBuildTime) {
            Log::warning(sprintf(
                'Your database was last managed by an older version of Firefly III (I see %s, I expect %s). Redirect to migrate routine.',
                $dbTime->format('Y-m-d H:i:s'),
                $configTime->format('Y-m-d H:i:s')
            ));

            return true;
        }
        Log::debug(sprintf('Your database is up to date (I see %s, I expect %s).', $dbTime->format('Y-m-d H:i:s'), $configTime->format('Y-m-d H:i:s')));

        return false;
    }
}
