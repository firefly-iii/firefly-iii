<?php
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
        $configVersion = (string)config('firefly.version');
        $dbVersion     = (string)FireflyConfig::getFresh('ff3_version', '1.0')->data;
        $compare       = 0;
        // compare develop to develop
        if (str_starts_with($configVersion, 'develop') && str_starts_with($dbVersion, 'develop')) {
            $compare = $this->compareDevelopVersions($configVersion, $dbVersion);
        }
        // user has develop installed, goes to normal version.
        if (!str_starts_with($configVersion, 'develop') && str_starts_with($dbVersion, 'develop')) {
            return true;
        }

        // user has normal, goes to develop version.
        if (str_starts_with($configVersion, 'develop') && !str_starts_with($dbVersion, 'develop')) {
            return true;
        }

        // compare normal with normal.
        if (!str_starts_with($configVersion, 'develop') && !str_starts_with($dbVersion, 'develop')) {
            $compare = version_compare($configVersion, $dbVersion);
        }
        if (-1 === $compare) {
            Log::warning(sprintf('The current configured Firefly III version (%s) is older than the required version (%s). Redirect to migrate routine.', $dbVersion, $configVersion));

            return true;
        }
        return false;
    }
}
