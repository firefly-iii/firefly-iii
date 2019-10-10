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
use FireflyIII\Services\Github\Object\Release;
use FireflyIII\Services\Github\Request\UpdateRequest;
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
     * @return Release|null
     */
    public function getLatestRelease(): ?Release
    {
        Log::debug('Now in getLatestRelease()');
        $return = null;
        /** @var UpdateRequest $request */
        $request = app(UpdateRequest::class);
        try {
            $request->call();
        } catch (FireflyException $e) {
            Log::error(sprintf('Could not check for updates: %s', $e->getMessage()));

            return null;
        }

        // get releases from array.
        $releases = $request->getReleases();

        Log::debug(sprintf('Found %d releases', count($releases)));

        if (count($releases) > 0) {
            // first entry should be the latest entry:
            /** @var Release $first */
            $first  = reset($releases);
            $return = $first;
            Log::debug(sprintf('Number of releases found is larger than zero. Return %s ', $first->getTitle()));
        }

        return $return;
    }

    /**
     * Parses the version check result in a human readable sentence.
     *
     * @param int          $versionCheck
     * @param Release|null $release
     *
     * @return string
     */
    public function parseResult(int $versionCheck, Release $release = null): string
    {
        Log::debug(sprintf('Now in parseResult(%d)', $versionCheck));
        $current   = (string)config('firefly.version');
        $return    = '';
        $triggered = false;
        if ($versionCheck === -2) {
            Log::debug('-2, so give error.');
            $return    = (string)trans('firefly.update_check_error');
            $triggered = true;
        }
        if ($versionCheck === -1 && null !== $release) {
            $triggered = true;
            Log::debug('New version!');
            // there is a new FF version!
            // has it been released for at least three days?
            $today       = new Carbon;
            $releaseDate = $release->getUpdated();
            if ($today->diffInDays($releaseDate) > 3) {
                Log::debug('New version is older than 3 days!');
                $monthAndDayFormat = (string)trans('config.month_and_day');
                $return            = (string)trans(
                    'firefly.update_new_version_alert',
                    [
                        'your_version' => $current,
                        'new_version'  => $release->getTitle(),
                        'date'         => $release->getUpdated()->formatLocalized($monthAndDayFormat),
                    ]
                );
            }
        }

        if (0 === $versionCheck) {
            $triggered = true;
            Log::debug('User is running current version.');
            // you are running the current version!
            $return = (string)trans('firefly.update_current_version_alert', ['version' => $current]);
        }
        if (1 === $versionCheck && null !== $release) {
            $triggered = true;
            Log::debug('User is running NEWER version.');
            // you are running a newer version!
            $return = (string)trans('firefly.update_newer_version_alert', ['your_version' => $current, 'new_version' => $release->getTitle()]);
        }

        // @codeCoverageIgnoreStart
        if (false === $triggered) {
            Log::debug('No option was triggered.');
            $return = (string)trans('firefly.update_check_error');
        }
        // @codeCoverageIgnoreEnd

        return $return;
    }

    /**
     * Compare version and store result.
     *
     * @param Release|null $release
     *
     * @return int
     */
    public function versionCheck(Release $release = null): int
    {
        Log::debug('Now in versionCheck()');
        if (null === $release) {
            Log::debug('Release is null, return -2.');

            return -2;
        }
        $current = (string)config('firefly.version');
        $latest  = $release->getTitle();
        $check   = version_compare($current, $latest);
        Log::debug(sprintf('Comparing %s with %s, result is %s', $current, $latest, $check));

        return $check;
    }
}
