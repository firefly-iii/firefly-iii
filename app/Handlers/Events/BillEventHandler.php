<?php

/*
 * BillEventHandler.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\WarnUserAboutBill;
use FireflyIII\Mail\BillWarningMail;
use Log;
use Mail;

/**
 * Class BillEventHandler
 */
class BillEventHandler
{
    /**
     * @param WarnUserAboutBill $event
     * @return void
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function warnAboutBill(WarnUserAboutBill $event): void
    {
        $bill      = $event->bill;
        $field     = $event->field;
        $diff      = $event->diff;
        $user      = $bill->user;
        $address   = $user->email;
        $ipAddress = request()?->ip();

        // see if user has alternative email address:
        $pref = app('preferences')->getForUser($user, 'remote_guard_alt_email');
        if (null !== $pref) {
            $address = $pref->data;
        }

        // send message:
        Mail::to($address)->send(new BillWarningMail($bill, $field, $diff, $ipAddress));


        Log::debug('warnAboutBill');
    }

}
