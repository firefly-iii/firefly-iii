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
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

//use Illuminate\Notifications\Slack\SlackMessage;

/**
 * Class TestNotification
 */
class TestNotificationSlack extends Notification
{
    use Queueable;

    private OwnerNotifiable $owner;

    /**
     * Create a new notification instance.
     */
    public function __construct(OwnerNotifiable $owner)
    {
        $this->owner = $owner;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param OwnerNotifiable $notifiable
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return array
     */
    public function toArray(OwnerNotifiable $notifiable)
    {
        return [
        ];
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param OwnerNotifiable $notifiable
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     */
    public function toSlack(OwnerNotifiable $notifiable)
    {
        return new SlackMessage()->content((string) trans('email.admin_test_subject'));
        //return new SlackMessage()->text((string) trans('email.admin_test_subject'))->to($url);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param OwnerNotifiable $notifiable
     *
     * @return array
     */
    public function via(OwnerNotifiable $notifiable)
    {
        return ['slack'];
    }
}
