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

use FireflyIII\User;
use Illuminate\Auth\Events\Login;
use Log;
use Preferences;

/**
 * Class VersionCheckEventHandler
 */
class VersionCheckEventHandler
{
    /**
     * @param Login $event
     */
    public function checkForUpdates(Login $event)
    {
        // in Sandstorm, cannot check for updates:
        $sandstorm = 1 === intval(getenv('SANDSTORM'));
        if ($sandstorm === true) {
            return;
        }

        /** @var User $user */
        $user          = $event->user;
        $permission    = Preferences::getForUser($user, 'permission_update_check', -1);
        $lastCheckTime = Preferences::getForUser($user, 'last_update_check', time());
        $now           = time();
        if ($now - $lastCheckTime->data < 604800) {
            Log::debug('Checked for updates less than a week ago.');

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

    }

}