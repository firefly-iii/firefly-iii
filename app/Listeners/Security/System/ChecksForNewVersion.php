<?php

declare(strict_types=1);

/*
 * ChecksForNewVersion.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Listeners\Security\System;

use Carbon\Carbon;
use FireflyIII\Events\Security\System\SystemRequestedVersionCheck;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Update\UpdateTrait;
use FireflyIII\Models\Configuration;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Facades\FireflyConfig;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ChecksForNewVersion implements ShouldQueue
{
    use UpdateTrait;

    public function handle(SystemRequestedVersionCheck $event): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));

        // should not check for updates:
        $permission    = FireflyConfig::get('permission_update_check', -1);
        $value         = (int) $permission->data;
        if (1 !== $value) {
            Log::debug('Update check is not enabled.');
            $this->warnToCheckForUpdates($event);

            return;
        }

        /** @var UserRepositoryInterface $repository */
        $repository    = app(UserRepositoryInterface::class);
        $user          = $event->user;
        if (!$repository->hasRole($user, 'owner')) {
            Log::debug('User is not admin, done.');

            return;
        }

        /** @var Configuration $lastCheckTime */
        $lastCheckTime = FireflyConfig::get('last_update_check', Carbon::now()->getTimestamp());
        $now           = Carbon::now()->getTimestamp();
        $diff          = $now - $lastCheckTime->data;
        Log::debug(sprintf('Last check time is %d, current time is %d, difference is %d', $lastCheckTime->data, $now, $diff));
        if ($diff < 604800) {
            Log::debug(sprintf('Checked for updates less than a week ago (on %s).', Carbon::createFromTimestamp($lastCheckTime->data)->format('Y-m-d H:i:s')));

            return;
        }
        // last check time was more than a week ago.
        Log::debug('Have not checked for a new version in a week!');
        $release       = $this->getLatestRelease();
        $level         = 'info';
        $message       = trans('firefly.no_new_release_available');
        if ('' !== $release->getError()) {
            $level   = 'error';
            $message = $release->getError();
        }
        if ($release->isNewVersionAvailable()) {
            // if running develop, slightly different message.
            if (str_contains(config('firefly.version'), 'develop')) {
                $message = trans('firefly.update_current_dev_older', ['version'     => config('firefly.version'), 'new_version' => $release->getNewVersion()]);
            }
            if (!str_contains(config('firefly.version'), 'develop')) {
                $message = trans('firefly.update_new_version_alert', [
                    'your_version' => config('firefly.version'),
                    'new_version'  => $release->getNewVersion(),
                    'date'         => $release->getPublishedAt()->format('Y-m-d H:i:s'),
                ]);
            }
        }

        session()->flash($level, $message);
        FireflyConfig::set('last_update_check', Carbon::now()->getTimestamp());
    }

    /**
     * @throws FireflyException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function warnToCheckForUpdates(SystemRequestedVersionCheck $event): void
    {
        /** @var UserRepositoryInterface $repository */
        $repository    = app(UserRepositoryInterface::class);
        $user          = $event->user;
        if (!$repository->hasRole($user, 'owner')) {
            Log::debug('User is not admin, done.');

            return;
        }

        /** @var Configuration $lastCheckTime */
        $lastCheckTime = FireflyConfig::get('last_update_warning', Carbon::now()->getTimestamp());
        $now           = Carbon::now()->getTimestamp();
        $diff          = $now - $lastCheckTime->data;
        Log::debug(sprintf('Last warning time is %d, current time is %d, difference is %d', $lastCheckTime->data, $now, $diff));
        if ($diff < (604800 * 4)) {
            Log::debug(sprintf(
                'Warned about updates less than four weeks ago (on %s).',
                Carbon::createFromTimestamp($lastCheckTime->data)->format('Y-m-d H:i:s')
            ));

            return;
        }
        // last check time was more than a week ago.
        Log::debug('Have warned about a new version in four weeks!');

        session()->flash('info', (string) trans('firefly.disabled_but_check'));
        FireflyConfig::set('last_update_warning', Carbon::now()->getTimestamp());
    }
}
