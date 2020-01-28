<?php
/**
 * VersionCheckEventHandler.php
 * Copyright (c) 2019 james@firefly-iii.org
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
/** @noinspection MultipleReturnStatementsInspection */
/** @noinspection NullPointerExceptionInspection */
declare(strict_types=1);

namespace FireflyIII\Handlers\Events;


use FireflyIII\Events\RequestedVersionCheckStatus;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Update\UpdateTrait;
use FireflyIII\Models\Configuration;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Log;


/**
 * Class VersionCheckEventHandler
 */
class VersionCheckEventHandler
{
    use UpdateTrait;

    /**
     * Checks with GitHub to see if there is a new version.
     *
     * @param RequestedVersionCheckStatus $event
     */
    public function checkForUpdates(RequestedVersionCheckStatus $event): void
    {
        Log::debug('Now in checkForUpdates()');

        // should not check for updates:
        $permission = app('fireflyconfig')->get('permission_update_check', -1);
        $value      = (int)$permission->data;
        if (1 !== $value) {
            Log::info('Update check is not enabled.');

            return;
        }

        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        /** @var User $user */
        $user = $event->user;
        if (!$repository->hasRole($user, 'owner')) {
            Log::debug('User is not admin, done.');

            return;
        }

        /** @var Configuration $lastCheckTime */
        $lastCheckTime = app('fireflyconfig')->get('last_update_check', time());
        $now           = time();
        $diff          = $now - $lastCheckTime->data;
        Log::debug(sprintf('Last check time is %d, current time is %d, difference is %d', $lastCheckTime->data, $now, $diff));
        if ($diff < 604800) {
            Log::debug(sprintf('Checked for updates less than a week ago (on %s).', date('Y-m-d H:i:s', $lastCheckTime->data)));

            return;
        }
        // last check time was more than a week ago.
        Log::debug('Have not checked for a new version in a week!');
        try {
            $latestRelease = $this->getLatestRelease();
        } catch (FireflyException $e) {
            Log::error($e);
            session()->flash('error', (string)trans('firefly.update_check_error'));

            // softfail.
            return;
        }
        $versionCheck  = $this->versionCheck($latestRelease);
        $resultString  = $this->parseResult($versionCheck, $latestRelease);
        if (0 !== $versionCheck && '' !== $resultString) {
            // flash info
            session()->flash('info', $resultString);
        }
        app('fireflyconfig')->set('last_update_check', time());
    }
}
