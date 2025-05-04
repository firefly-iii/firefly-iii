<?php

/*
 * UserRegistration.php
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

namespace FireflyIII\Notifications\Admin;

use FireflyIII\Notifications\Notifiables\OwnerNotifiable;
use FireflyIII\Notifications\ReturnsAvailableChannels;
use FireflyIII\Notifications\ReturnsSettings;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use NotificationChannels\Pushover\PushoverMessage;
use Ntfy\Message;

/**
 * Class UserRegistration
 */
class UserRegistration extends Notification
{
    use Queueable;

    public function __construct(private User $user)
    {
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function toArray(OwnerNotifiable $notifiable): array
    {
        return [
        ];
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function toMail(OwnerNotifiable $notifiable): MailMessage
    {
        $ip        = Request::ip();
        $host      = Steam::getHostName($ip);
        $userAgent = Request::userAgent();
        $time      = now(config('app.timezone'))->isoFormat((string) trans('config.date_time_js'));

        return new MailMessage()
            ->markdown('emails.registered-admin', ['email' => $this->user->email, 'id' => $this->user->id, 'ip' => $ip, 'host' => $host, 'userAgent' => $userAgent, 'time' => $time])
            ->subject((string) trans('email.registered_subject_admin'))
        ;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function toNtfy(OwnerNotifiable $notifiable): Message
    {
        Log::debug('Now in toNtfy() for (Admin) UserRegistration');
        $settings = ReturnsSettings::getSettings('ntfy', 'owner', null);
        $message  = new Message();
        $message->topic($settings['ntfy_topic']);
        $message->title((string) trans('email.registered_subject_admin'));
        $message->body((string) trans('email.admin_new_user_registered', ['email' => $this->user->email, 'invitee' => $this->user->email]));

        return $message;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function toPushover(OwnerNotifiable $notifiable): PushoverMessage
    {
        Log::debug('Now in toPushover() for UserRegistration');

        return PushoverMessage::create((string) trans('email.admin_new_user_registered', ['email' => $this->user->email, 'invitee' => $this->user->email]))
            ->title((string) trans('email.registered_subject_admin'))
        ;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function toSlack(OwnerNotifiable $notifiable): SlackMessage
    {
        return new SlackMessage()->content((string) trans('email.admin_new_user_registered', ['email' => $this->user->email, 'id' => $this->user->id]));
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function via(OwnerNotifiable $notifiable): array
    {
        return ReturnsAvailableChannels::returnChannels('owner');
    }
}
