<?php

declare(strict_types=1);

/*
 * NotifiesAboutExtensionOrRenewal.php
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

namespace FireflyIII\Listeners\Model\Subscription;

use FireflyIII\Events\Model\Subscription\SubscriptionNeedsExtensionOrRenewal;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\User\BillReminder;
use FireflyIII\Support\Facades\Preferences;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifiesAboutExtensionOrRenewal implements ShouldQueue
{
    public function handle(SubscriptionNeedsExtensionOrRenewal $event): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        $subscription = $event->subscription;

        /** @var bool $preference */
        $preference   = Preferences::getForUser($subscription->user, 'notification_bill_reminder', true)->data;

        if (true === $preference) {
            Log::debug('Subscription reminder is true!');
            NotificationSender::send($subscription->user, new BillReminder($subscription, $event->field, $event->diff));

            return;
        }
        Log::debug('User has disabled subscription reminders.');
    }
}
