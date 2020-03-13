<?php
/**
 * Telemetry.php
 * Copyright (c) 2020 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support;

use Log;

/**
 * Class Telemetry
 */
class Telemetry
{
    /**
     * Feature telemetry stores a boolean "true" for the given $flag.
     *
     * Examples:
     * - use-help-pages
     * - has-created-bill
     * - do-big-import
     * - first-time-install
     * - more
     *
     * Its use should be limited to exotic and strange use cases in Firefly III.
     * Because time and date are logged as well, useful to track users' evolution in Firefly III.
     *
     * Any meta-data stored is strictly non-financial.
     *
     * @param string $flag
     */
    public function feature(string $flag): void
    {
        if (false === config('firefly.send_telemetry')) {
            // hard stop if not allowed to do telemetry.
            // do nothing!
            return;
        }
        Log::info(sprintf('Logged telemetry feature flag "%s".', $flag));

        // no storage backend yet, do nothing.
    }

    /**
     * String telemetry stores a string value as a telemetry entry. Values could include:
     *
     * - "php-version", "php7.3"
     * - "os-version", "linux"
     *
     * Any meta-data stored is strictly non-financial.
     *
     * @param string $name
     * @param string $value
     */
    public function string(string $name, string $value): void
    {
        if (false === config('firefly.send_telemetry')) {
            // hard stop if not allowed to do telemetry.
            // do nothing!
            return;
        }
        Log::info(sprintf('Logged telemetry string "%s" with value "%s".', $name, $value));
    }

}