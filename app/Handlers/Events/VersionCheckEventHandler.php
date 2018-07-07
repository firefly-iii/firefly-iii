<?php
/**
 * VersionCheckEventHandler.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
/** @noinspection MultipleReturnStatementsInspection */
/** @noinspection NullPointerExceptionInspection */
declare(strict_types=1);

namespace FireflyIII\Handlers\Events;

use Carbon\Carbon;
use FireflyConfig;
use FireflyIII\Events\RequestedVersionCheckStatus;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Services\Github\Object\Release;
use FireflyIII\Services\Github\Request\UpdateRequest;
use FireflyIII\User;
use Log;

/**
 * Class VersionCheckEventHandler
 */
class VersionCheckEventHandler
{

    /**
     * Checks with GitHub to see if there is a new version.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @param RequestedVersionCheckStatus $event
     */
    public function checkForUpdates(RequestedVersionCheckStatus $event): void
    {
        // in Sandstorm, cannot check for updates:
        $sandstorm = 1 === (int)getenv('SANDSTORM');
        if (true === $sandstorm) {
            return; // @codeCoverageIgnore
        }

        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        /** @var User $user */
        $user = $event->user;
        if (!$repository->hasRole($user, 'owner')) {
            return;
        }

        $permission    = FireflyConfig::get('permission_update_check', -1);
        $lastCheckTime = FireflyConfig::get('last_update_check', time());
        $now           = time();
        $diff          = $now - $lastCheckTime->data;
        Log::debug(sprintf('Difference is %d seconds.', $diff));
        if ($diff < 604800) {
            Log::debug(sprintf('Checked for updates less than a week ago (on %s).', date('Y-m-d H:i:s', $lastCheckTime->data)));

            return;

        }
        // last check time was more than a week ago.
        Log::debug('Have not checked for a new version in a week!');

        // have actual permission?
        if ($permission->data === -1) {
            // never asked before.
            session()->flash('info', (string)trans('firefly.check_for_updates_permission', ['link' => route('admin.update-check')]));

            return;
        }

        $current       = config('firefly.version');
        $latestRelease = $this->getLatestRelease();
        $versionCheck  = $this->versionCheck($latestRelease);
        $string        = '';
        if ($versionCheck === -2) {
            $string = (string)trans('firefly.update_check_error');
        }
        if ($versionCheck === -1 && null !== $latestRelease) {
            // there is a new FF version!
            // has it been released for at least three days?
            $today = new Carbon;
            if ($today->diffInDays($latestRelease->getUpdated(), true) > 3) {
                $monthAndDayFormat = (string)trans('config.month_and_day');
                $string            = (string)trans(
                    'firefly.update_new_version_alert',
                    [
                        'your_version' => $current,
                        'new_version'  => $latestRelease->getTitle(),
                        'date'         => $latestRelease->getUpdated()->formatLocalized($monthAndDayFormat),
                    ]
                );
            }
        }
        if (0 !== $versionCheck && '' !== $string) {
            // flash info
            session()->flash('info', $string);
        }
        FireflyConfig::set('last_update_check', time());
    }

    /**
     * Get object for the latest release from GitHub.
     *
     * @return Release|null
     */
    private function getLatestRelease(): ?Release
    {
        $return = null;
        /** @var UpdateRequest $request */
        $request = app(UpdateRequest::class);
        try {
            $request->call();
        } catch (FireflyException $e) {
            Log::error(sprintf('Could not check for updates: %s', $e->getMessage()));
        }

        // get releases from array.
        $releases = $request->getReleases();
        if (\count($releases) > 0) {
            // first entry should be the latest entry:
            /** @var Release $first */
            $first  = reset($releases);
            $return = $first;
        }

        return $return;
    }

    /**
     * Compare version and store result.
     *
     * @param Release|null $release
     *
     * @return int
     */
    private function versionCheck(Release $release = null): int
    {
        if (null === $release) {
            return -2;
        }
        $current = (string)config('firefly.version');
        $check   = version_compare($current, $release->getTitle());
        Log::debug(sprintf('Comparing %s with %s, result is %s', $current, $release->getTitle(), $check));

        return $check;
    }

}
