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

use Exception;
use FireflyIII\Events\Model\Bill\WarnUserAboutBill;
use FireflyIII\Events\Model\Bill\WarnUserAboutOverdueSubscription;
use FireflyIII\Notifications\User\BillReminder;
use FireflyIII\Notifications\User\SubscriptionOverdueReminder;
use FireflyIII\Support\Facades\Preferences;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Class BillEventHandler
 */
class BillEventHandler
{
    public function warnAboutOverdueSubscription(WarnUserAboutOverdueSubscription $event): void
    {
        $bill  = $event->bill;
        $dates = $event->dates;

        $key  = sprintf('bill_overdue_%s_%s', $bill->id, substr(hash('sha256', json_encode($dates['pay_dates'], JSON_THROW_ON_ERROR)), 0, 10));
        $pref = Preferences::getForUser($bill->user, $key, false);
        if (true === $pref->data) {
            Log::debug(sprintf('User %s has already been warned about overdue subscription %s.', $bill->user->id, $bill->id));
            return;
        }
        /** @var bool $sendNotification */
        $sendNotification = Preferences::getForUser($bill->user, 'notification_bill_reminder', true)->data;

        if (true === $sendNotification) {
            Log::debug('Will warning about overdue subscription.');
            Preferences::setForUser($bill->user, $key, true);

            try {
                Notification::send($bill->user, new SubscriptionOverdueReminder($bill, $dates));
            } catch (Exception $e) {
                $message = $e->getMessage();
                if (str_contains($message, 'Bcc')) {
                    Log::warning('[Bcc] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                    return;
                }
                if (str_contains($message, 'RFC 2822')) {
                    Log::warning('[RFC] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                    return;
                }
                Log::error($e->getMessage());
                Log::error($e->getTraceAsString());
            }
            return;
        }
        Log::debug('User has disabled bill reminders.');
    }

    public function warnAboutBill(WarnUserAboutBill $event): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));

        $bill = $event->bill;

        /** @var bool $preference */
        Preferences::getForUser($bill->user, 'notification_bill_reminder', true)->data;

        if (true === $preference) {
            Log::debug('Bill reminder is true!');

            try {
                Notification::send($bill->user, new BillReminder($bill, $event->field, $event->diff));
            } catch (Exception $e) {
                $message = $e->getMessage();
                if (str_contains($message, 'Bcc')) {
                    Log::warning('[Bcc] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                    return;
                }
                if (str_contains($message, 'RFC 2822')) {
                    Log::warning('[RFC] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                    return;
                }
                Log::error($e->getMessage());
                Log::error($e->getTraceAsString());
            }
            return;
        }
        Log::debug('User has disabled bill reminders.');
    }
}
