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
use FireflyIII\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Class TestNotification
 */
class UserTestNotificationEmail extends Notification
{
    use Queueable;

    private User $user;


    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the array representation of the notification.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return array
     */
    public function toArray(User $notifiable)
    {
        return [
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return MailMessage
     */
    public function toMail(User $notifiable)
    {
        $address = (string) $notifiable->email;

        return (new MailMessage())
            ->markdown('emails.admin-test', ['email' => $address])
            ->subject((string) trans('email.admin_test_subject'))
        ;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param User $notifiable
     *
     * @return array
     */
    public function via(User $notifiable)
    {
        return ['mail'];
    }
}
