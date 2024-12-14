<?php

/*
 * UserInvitation.php
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

use FireflyIII\Models\InvitedUser;
use FireflyIII\Notifications\Notifiables\OwnerNotifiable;
use FireflyIII\Notifications\ReturnsAvailableChannels;
use FireflyIII\Notifications\ReturnsSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Pushover\PushoverMessage;
use Ntfy\Message;

/**
 * Class UserInvitation
 */
class UserInvitation extends Notification
{
    use Queueable;

    private InvitedUser     $invitee;
    private OwnerNotifiable $owner;

    /**
     * Create a new notification instance.
     */
    public function __construct(OwnerNotifiable $owner, InvitedUser $invitee)
    {
        $this->invitee = $invitee;
        $this->owner   = $owner;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return array
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
            ->markdown('emails.invitation-created', ['email' => $this->invitee->user->email, 'invitee' => $this->invitee->email])
            ->subject((string) trans('email.invitation_created_subject'))
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
        return (new SlackMessage())->content(
            (string) trans('email.invitation_created_body', ['email' => $this->invitee->user->email, 'invitee' => $this->invitee->email])
        );
    }

    public function toPushover(OwnerNotifiable $notifiable): PushoverMessage
    {
        Log::debug('Now in toPushover() for UserInvitation');

        return PushoverMessage::create((string) trans('email.invitation_created_body', ['email' => $this->invitee->user->email, 'invitee' => $this->invitee->email]))
            ->title((string) trans('email.invitation_created_subject'))
        ;
    }

    public function toNtfy(OwnerNotifiable $notifiable): Message
    {
        Log::debug('Now in toNtfy() for UserInvitation');
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

        $message  = new Message();
        $message->topic($settings['ntfy_topic']);
        $message->title((string) trans('email.invitation_created_subject'));
        $message->body((string) trans('email.invitation_created_body', ['email' => $this->invitee->user->email, 'invitee' => $this->invitee->email]));

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
