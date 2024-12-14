<?php

/*
 * TestNotification.php
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

namespace FireflyIII\Notifications\Test;

use FireflyIII\Notifications\Notifiables\OwnerNotifiable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Pushover\PushoverChannel;
use NotificationChannels\Pushover\PushoverMessage;

// use Illuminate\Notifications\Slack\SlackMessage;

/**
 * Class TestNotification
 */
class OwnerTestNotificationPushover extends Notification
{
    use Queueable;

    private OwnerNotifiable $owner;


    public function __construct(OwnerNotifiable $owner)
    {
        $this->owner = $owner;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toArray(OwnerNotifiable $notifiable)
    {
        return [
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toPushover(OwnerNotifiable $notifiable): PushoverMessage
    {
        Log::debug('Now in toPushover()');

        return PushoverMessage::create((string) trans('email.admin_test_message', ['channel' => 'Pushover']))
                              ->title((string) trans('email.admin_test_subject'));
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function via(OwnerNotifiable $notifiable)
    {
        return [PushoverChannel::class];
    }
}
