<?php

/*
 * UserNewPassword.php
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

namespace FireflyIII\Notifications\User;

use FireflyIII\Notifications\ReturnsAvailableChannels;
use FireflyIII\Notifications\ReturnsSettings;
use FireflyIII\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Pushover\PushoverMessage;
use Ntfy\Message;

/**
 * Class UserNewPassword
 */
class UserNewPassword extends Notification
{
    use Queueable;

    private string $url;


    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toArray(User $notifiable)
    {
        return [
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toMail(User $notifiable)
    {
        return (new MailMessage())
            ->markdown('emails.password', ['url' => $this->url])
            ->subject((string) trans('email.reset_pw_subject'));
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toSlack(User $notifiable)
    {
        return new SlackMessage()->content((string) trans('email.reset_pw_message'));
    }

    public function toNtfy(User $notifiable): Message
    {
        $settings = ReturnsSettings::getSettings('ntfy', 'user', $notifiable);
        $message  = new Message();
        $message->topic($settings['ntfy_topic']);
        $message->body((string) trans('email.reset_pw_message'));

        return $message;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toPushover(User $notifiable): PushoverMessage
    {
        return PushoverMessage::create((string) trans('email.reset_pw_message'));
    }

    public function via(User $notifiable)
    {
        return ReturnsAvailableChannels::returnChannels('user', $notifiable);
    }
}
