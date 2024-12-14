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
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Ntfy\Message;
use Wijourdil\NtfyNotificationChannel\Channels\NtfyChannel;

// use Illuminate\Notifications\Slack\SlackMessage;

/**
 * Class TestNotification
 */
class OwnerTestNotificationNtfy extends Notification
{
    use Queueable;

    public OwnerNotifiable $owner;


    public function __construct(OwnerNotifiable $owner)
    {
        $this->owner = $owner;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toArray($notifiable)
    {
        return [
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toNtfy(OwnerNotifiable $notifiable): Message
    {
        $settings = ReturnsSettings::getSettings('ntfy', 'owner', null);
        $message  = new Message();
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
