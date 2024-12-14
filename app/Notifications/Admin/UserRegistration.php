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
use FireflyIII\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Pushover\PushoverMessage;
use Ntfy\Message;

/**
 * Class UserRegistration
 */
class UserRegistration extends Notification
{
    use Queueable;

    private OwnerNotifiable $owner;
    private User            $user;

    /**
     * Create a new notification instance.
     */
    public function __construct(OwnerNotifiable $owner, User $user)
    {
        $this->user  = $user;
        $this->owner = $owner;
    }

    /**
     * Get the array representation of the notification.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toArray(OwnerNotifiable $notifiable)
    {
        return [
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return MailMessage
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toMail(OwnerNotifiable $notifiable)
    {
        return (new MailMessage())
            ->markdown('emails.registered-admin', ['email' => $this->user->email, 'id' => $this->user->id])
            ->subject((string) trans('email.registered_subject_admin'))
        ;
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return SlackMessage
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toSlack(OwnerNotifiable $notifiable)
    {
        return (new SlackMessage())->content((string) trans('email.admin_new_user_registered', ['email' => $this->user->email, 'id' => $this->user->id]));
    }

    public function toPushover(OwnerNotifiable $notifiable): PushoverMessage
    {
        Log::debug('Now in toPushover() for UserRegistration');

        return PushoverMessage::create((string) trans('email.admin_new_user_registered', ['email' => $this->user->email, 'invitee' => $this->user->email]))
            ->title((string) trans('email.registered_subject_admin'))
        ;
    }

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
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function via(OwnerNotifiable $notifiable)
    {
        return ReturnsAvailableChannels::returnChannels('owner');
    }
}
