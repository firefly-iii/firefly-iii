<?php

declare(strict_types=1);

/*
 * StoresNewIpAddress.php
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

namespace FireflyIII\Listeners\Security\User;

use Carbon\Carbon;
use FireflyIII\Events\Security\User\UserLoggedInFromNewIpAddress;
use FireflyIII\Events\Security\User\UserSuccessfullyLoggedIn;
use FireflyIII\Support\Facades\Preferences;
use Illuminate\Support\Facades\Log;

class StoresNewIpAddress
{
    public function handle(UserSuccessfullyLoggedIn $event): void
    {
        Log::debug('Now in storeUserIPAddress');
        $user       = $event->user;

        if ($user->hasRole('demo')) {
            Log::debug('Do not log demo user logins');

            return;
        }

        /** @var array $preference */
        $preference = Preferences::getForUser($user, 'login_ip_history', [])->data;
        $inArray    = false;
        $ip         = request()->ip();
        Log::debug(sprintf('User logging in from IP address %s', $ip));

        // update array if in array
        foreach ($preference as $index => $row) {
            if ($row['ip'] === $ip) {
                Log::debug('Found IP in array, refresh time.');
                $preference[$index]['time'] = now(config('app.timezone'))->format('Y-m-d H:i:s');
                $inArray                    = true;
            }
            // clean up old entries (6 months)
            $carbon = Carbon::createFromFormat('Y-m-d H:i:s', $preference[$index]['time']);
            if ($carbon instanceof Carbon && $carbon->diffInMonths(today(), true) > 6) {
                Log::debug(sprintf('Entry for %s is very old, remove it.', $row['ip']));
                unset($preference[$index]);
            }
        }
        // add to array if not the case:
        if (false === $inArray) {
            $preference[] = ['ip'       => $ip, 'time'     => now(config('app.timezone'))->format('Y-m-d H:i:s'), 'notified' => false];
        }
        $preference = array_values($preference);

        /** @var bool $send */
        $send       = Preferences::getForUser($user, 'notification_user_login', true)->data;
        Preferences::setForUser($user, 'login_ip_history', $preference);

        if (false === $inArray && true === $send) {
            event(new UserLoggedInFromNewIpAddress($user));
        }
    }
}
