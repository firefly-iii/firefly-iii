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
use FireflyIII\Notifications\User\BillReminder;
use Illuminate\Support\Facades\Notification;
use Exception;

/**
 * Class BillEventHandler
 */
class BillEventHandler
{
    public function warnAboutBill(WarnUserAboutBill $event): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));

        $bill       = $event->bill;

        /** @var bool $preference */
        $preference = app('preferences')->getForUser($bill->user, 'notification_bill_reminder', true)->data;

        if (true === $preference) {
            app('log')->debug('Bill reminder is true!');

            try {
                Notification::send($bill->user, new BillReminder($bill, $event->field, $event->diff));
            } catch (Exception $e) {
                $message = $e->getMessage();
                if (str_contains($message, 'Bcc')) {
                    app('log')->warning('[Bcc] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                    return;
                }
                if (str_contains($message, 'RFC 2822')) {
                    app('log')->warning('[RFC] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                    return;
                }
                app('log')->error($e->getMessage());
                app('log')->error($e->getTraceAsString());
            }
        }
        if (false === $preference) {
            app('log')->debug('User has disabled bill reminders.');
        }
    }
}
