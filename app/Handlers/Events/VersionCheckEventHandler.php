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

declare(strict_types=1);

namespace FireflyIII\Handlers\Events;

use FireflyConfig;
use FireflyIII\Events\RequestedVersionCheckStatus;
use FireflyIII\Exceptions\FireflyException;
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
     * @param RequestedVersionCheckStatus $event
     */
    public function checkForUpdates(RequestedVersionCheckStatus $event)
    {
        // in Sandstorm, cannot check for updates:
        $sandstorm = 1 === intval(getenv('SANDSTORM'));
        if ($sandstorm === true) {
            return;
        }


        /** @var User $user */
        $user = $event->user;
        if (!$user->hasRole('owner')) {
            return;
        }

        $permission    = FireflyConfig::get('permission_update_check', -1);
        $lastCheckTime = FireflyConfig::get('last_update_check', time());
        $now           = time();
        if ($now - $lastCheckTime->data < 604800) {
            Log::debug(sprintf('Checked for updates less than a week ago (on %s).', date('Y-m-d H:i:s', $lastCheckTime->data)));

            return;

        }
        // last check time was more than a week ago.
        Log::debug('Have not checked for a new version in a week!');

        // have actual permission?
        if ($permission->data === -1) {
            // never asked before.
            session()->flash('info', strval(trans('firefly.check_for_updates_permission', ['link' => route('admin.update-check')])));

            return;
        }

        $current = config('firefly.version');
        /** @var UpdateRequest $request */
        $request = app(UpdateRequest::class);
        $check   = -2;
        $first   = new Release(['id' => '0', 'title' => '0', 'updated' => '2017-01-01', 'content' => '']);
        try {
            $request->call();
            $releases = $request->getReleases();
            // first entry should be the latest entry:
            /** @var Release $first */
            $first = reset($releases);
            $check = version_compare($current, $first->getTitle());
            FireflyConfig::set('last_update_check', time());
        } catch (FireflyException $e) {
            Log::error(sprintf('Could not check for updates: %s', $e->getMessage()));
        }
        $string = 'no result: ' . $check;
        if ($check === -2) {
            $string = strval(trans('firefly.update_check_error'));
        }
        if ($check === -1) {
            // there is a new FF version!
            $monthAndDayFormat = (string)trans('config.month_and_day');
            $string            = strval(
                trans(
                    'firefly.update_new_version_alert',
                    ['your_version' => $current, 'new_version' => $first->getTitle(), 'date' => $first->getUpdated()->formatLocalized($monthAndDayFormat)]
                )
            );
        }
        if ($check !== 0) {
            // flash info
            session()->flash('info', $string);
        }

        return;
    }

}