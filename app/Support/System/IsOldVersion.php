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
use FireflyIII\Support\Facades\FireflyConfig;
use Illuminate\Support\Facades\Log;

trait IsOldVersion
{
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

        $currentDate = Carbon::createFromFormat('!Y-m-d', $currentParts[1]);
        $latestDate  = Carbon::createFromFormat('!Y-m-d', $latestParts[1]);

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
        $dbBuildTime     = (int) FireflyConfig::getFresh('ff3_build_time', 123)->data;
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
