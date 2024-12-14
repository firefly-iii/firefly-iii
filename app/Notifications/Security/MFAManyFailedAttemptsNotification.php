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

use FireflyIII\Notifications\ReturnsAvailableChannels;
use FireflyIII\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class MFAManyFailedAttemptsNotification extends Notification
{
    use Queueable;

    private int  $count;
    private User $user;

    public function __construct(User $user, int $count)
    {
        $this->user  = $user;
        $this->count = $count;
    }


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
        $subject = (string) trans('email.mfa_many_failed_subject', ['count' => $this->count]);

        return (new MailMessage())->markdown('emails.security.many-failed-attempts', ['user' => $this->user, 'count' => $this->count])->subject($subject);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toSlack(User $notifiable)
    {
        $message = (string) trans('email.mfa_many_failed_slack', ['email' => $this->user->email, 'count' => $this->count]);

        return (new SlackMessage())->content($message);
    }


    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function via(User $notifiable)
    {
        return ReturnsAvailableChannels::returnChannels('user', $notifiable);
    }
}
