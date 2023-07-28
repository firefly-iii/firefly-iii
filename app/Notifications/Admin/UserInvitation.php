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
use FireflyIII\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

/**
 * Class UserInvitation
 */
class UserInvitation extends Notification
{
    use Queueable;

    private InvitedUser $invitee;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(InvitedUser $invitee)
    {
        $this->invitee = $invitee;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->markdown('emails.invitation-created', ['email' => $this->invitee->user->email, 'invitee' => $this->invitee->email])
            ->subject((string)trans('email.invitation_created_subject'));
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return SlackMessage
     */
    public function toSlack($notifiable)
    {
        return (new SlackMessage())->content(
            (string)trans('email.invitation_created_body', ['email' => $this->invitee->user->email, 'invitee' => $this->invitee->email])
        );
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        /** @var User|null $user */
        $user = auth()->user();
        $slackUrl = null === $user ? '' : (string)app('preferences')->getForUser(auth()->user(), 'slack_webhook_url', '')->data;
        if (str_starts_with($slackUrl, 'https://hooks.slack.com/services/')) {
            return ['mail', 'slack'];
        }
        return ['mail'];
    }
}
