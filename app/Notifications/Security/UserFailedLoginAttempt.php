<?php

/*
 * UserFailedLoginAttempt.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Notifications\Security;

use FireflyIII\Notifications\ReturnsAvailableChannels;
use FireflyIII\Notifications\ReturnsSettings;
use FireflyIII\Support\Facades\FireflyConfig;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Request;
use NotificationChannels\Pushover\PushoverMessage;
use Ntfy\Message;

class UserFailedLoginAttempt extends Notification
{
    use Queueable;

    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function toArray(User $notifiable): array
    {
        return [
        ];
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function toMail(User $notifiable): MailMessage
    {
        $subject   = (string) trans('email.failed_login_subject');
        $ip        = Request::ip();
        $host      = Steam::getHostName($ip);
        $userAgent = Request::userAgent();
        $time      = now(config('app.timezone'))->isoFormat((string) trans('config.date_time_js'));

        return (new MailMessage())->markdown('emails.security.failed-login', ['user' => $this->user, 'ip' => $ip, 'host' => $host, 'userAgent' => $userAgent, 'time' => $time])->subject($subject);
    }

    public function toNtfy(User $notifiable): Message
    {
        $settings = ReturnsSettings::getSettings('ntfy', 'user', $notifiable);
        $message  = new Message();
        $message->topic($settings['ntfy_topic']);
        $message->title((string) trans('email.failed_login_subject'));
        $message->body((string) trans('email.failed_login_message', ['email' => $this->user->email]));

        return $message;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function toPushover(User $notifiable): PushoverMessage
    {
        return PushoverMessage::create((string) trans('email.failed_login_message', ['email' => $this->user->email]))
            ->title((string) trans('email.failed_login_subject'))
        ;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function toSlack(User $notifiable): SlackMessage
    {
        $message = (string) trans('email.failed_login_message', ['email' => $this->user->email]);

        return new SlackMessage()->content($message);
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function via(User $notifiable): array
    {
        $channels   = ReturnsAvailableChannels::returnChannels('user', $notifiable);
        $isDemoSite = FireflyConfig::get('is_demo_site',false)->data;
        if (true === $isDemoSite) {
            return array_diff($channels, ['mail']);
        }

        return $channels;
    }
}
