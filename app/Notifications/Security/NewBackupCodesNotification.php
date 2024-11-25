<?php

/*
 * EnabledMFANotification.php
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

use FireflyIII\Support\Notifications\UrlValidator;
use FireflyIII\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class NewBackupCodesNotification extends Notification
{
    use Queueable;

    private User   $user;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
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
    public function toArray($notifiable)
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
    public function toMail($notifiable)
    {
        $subject = (string)trans('email.new_backup_codes_subject');

        return (new MailMessage())->markdown('emails.security.new-backup-codes', ['user' => $this->user])->subject($subject);
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
    public function toSlack($notifiable)
    {
        $message = (string)trans('email.new_backup_codes_slack', ['email' => $this->user->email]);

        return (new SlackMessage())->content($message);
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
    public function via($notifiable)
    {
        /** @var null|User $user */
        $user     = auth()->user();
        $slackUrl = null === $user ? '' : app('preferences')->getForUser(auth()->user(), 'slack_webhook_url', '')->data;
        if (is_array($slackUrl)) {
            $slackUrl = '';
        }
        if (UrlValidator::isValidWebhookURL((string)$slackUrl)) {
            return ['mail', 'slack'];
        }

        return ['mail'];
    }
}
