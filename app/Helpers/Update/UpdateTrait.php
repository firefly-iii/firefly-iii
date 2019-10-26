<?php
/**
 * UpdateTrait.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Helpers\Update;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Services\FireflyIIIOrg\Update\UpdateRequestInterface;
use Log;

/**
 * Trait UpdateTrait
 *
 */
trait UpdateTrait
{
    /**
     * Get object for the latest release from GitHub.
     *
     * @return array
     * @throws FireflyException
     */
    public function getLatestRelease(): array
    {
        Log::debug('Now in getLatestRelease()');
        /** @var UpdateRequestInterface $checker */
        $checker = app(UpdateRequestInterface::class);
        $channel = app('fireflyconfig')->get('update_channel', 'stable')->data;

        return $checker->getVersion($channel);
    }

    /**
     * Parses the version check result in a human readable sentence.
     *
     * @param int    $versionCheck
     * @param string $version
     *
     * @return string
     */
    public function parseResult(int $versionCheck, array $information): string
    {
        Log::debug(sprintf('Now in parseResult(%d)', $versionCheck));
        $current   = (string)config('firefly.version');
        $return    = '';
        $triggered = false;
        if (-1 === $versionCheck) {
            $triggered         = true;
            $monthAndDayFormat = (string)trans('config.month_and_day');
            $carbon            = Carbon::createFromFormat('Y-m-d', $information['date']);
            $return            = (string)trans(
                'firefly.update_new_version_alert',
                [
                    'your_version' => $current,
                    'new_version'  => $information['version'],
                    'date'         => $carbon->formatLocalized($monthAndDayFormat),
                ]
            );
        }

        if (0 === $versionCheck) {
            $triggered = true;
            Log::debug('User is running current version.');
            // you are running the current version!
            $return = (string)trans('firefly.update_current_version_alert', ['version' => $current]);
        }
        if (1 === $versionCheck) {
            $triggered = true;
            Log::debug('User is running NEWER version.');
            // you are running a newer version!
            $return = (string)trans('firefly.update_newer_version_alert', ['your_version' => $current, 'new_version' => $information['version']]);
        }
        if (false === $triggered) {
            Log::debug('No option was triggered.');
            $return = (string)trans('firefly.update_check_error');
        }

        return $return;
    }

    /**
     * Compare version and store result.
     *
     * @param array $information
     *
     * @return int
     */
    public function versionCheck(array $information): int
    {
        Log::debug('Now in versionCheck()');
        $current = (string)config('firefly.version');
        $check   = version_compare($current, $information['version']);
        Log::debug(sprintf('Comparing %s with %s, result is %s', $current, $information['version'], $check), $information);

        return $check;
    }
}
