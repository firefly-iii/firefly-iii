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
use FireflyIII\Notifications\ReturnsSettings;
use FireflyIII\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Ntfy\Message;
use Wijourdil\NtfyNotificationChannel\Channels\NtfyChannel;

//use Illuminate\Notifications\Slack\SlackMessage;

/**
 * Class TestNotification
 */
class TestNotificationNtfy extends Notification
{
    use Queueable;

    public OwnerNotifiable $owner;

    /**
     * Create a new notification instance.
     */
    public function __construct(OwnerNotifiable $owner)
    {
        $this->owner = $owner;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
        ];
    }


    public function toNtfy(OwnerNotifiable $notifiable): Message
    {
        $settings = ReturnsSettings::getSettings('ntfy', 'owner', null);

        // overrule config.
        config(['ntfy-notification-channel.server' => $settings['ntfy_server']]);
        config(['ntfy-notification-channel.topic' => $settings['ntfy_topic']]);

        if ($settings['ntfy_auth']) {
            // overrule auth as well.
            config(['ntfy-notification-channel.authentication.enabled' => true]);
            config(['ntfy-notification-channel.authentication.username' => $settings['ntfy_user']]);
            config(['ntfy-notification-channel.authentication.password' => $settings['ntfy_pass']]);
        }

        $message = new Message();
        $message->topic($settings['ntfy_topic']);
        $message->title((string) trans('email.admin_test_subject'));
        $message->body((string) trans('email.admin_test_message', ['channel' => 'ntfy']));
        $message->tags(['white_check_mark']);

        return $message;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function via(OwnerNotifiable $notifiable)
    {
        return [NtfyChannel::class];
    }
}
