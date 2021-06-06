<?php

/**
 * Telemetry.php
 * Copyright (c) 2020 james@firefly-iii.org
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

declare(strict_types=1);

namespace FireflyIII\Support;

use Carbon\Carbon;
use FireflyIII\Support\System\GeneratesInstallationId;
use Sentry\Severity;
use Sentry\State\Scope;
use function Sentry\captureMessage;
use function Sentry\configureScope;

/**
 * Class Telemetry
 */
class Telemetry
{
    use GeneratesInstallationId;

    /**
     * Feature telemetry stores a $value for the given $feature.
     * Will only store the given $feature / $value combination once.
     *
     *
     * Examples:
     * - execute-cli-command [value]
     * - use-help-pages
     * - has-created-bill
     * - first-time-install
     * - more
     *
     * Its use should be limited to exotic and strange use cases in Firefly III.
     * Because time and date are logged as well, useful to track users' evolution in Firefly III.
     *
     * Any meta-data stored is strictly non-financial.
     *
     * @param string $key
     * @param string $value
     */
    public function feature(string $key, string $value): void
    {
        if (false === config('firefly.send_telemetry') || false === config('firefly.feature_flags.telemetry')) {
            // hard stop if not allowed to do telemetry.
            // do nothing!
            return;
        }
        $this->generateInstallationId();
        $installationId = app('fireflyconfig')->get('installation_id');

        // add some context:
        configureScope(
            function (Scope $scope) use ($installationId, $key, $value): void {
                $scope->setContext(
                    'telemetry', [
                                   'installation_id' => $installationId->data,
                                   'version'         => config('firefly.version'),
                                   'collected_at'    => Carbon::now()->format('r'),
                                   'key'             => $key,
                                   'value'           => $value,
                               ]
                );
            }
        );
        captureMessage(sprintf('FIT: %s/%s', $key, $value), Severity::info());
    }

}
