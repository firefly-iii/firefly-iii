<?php

/*
 * TransactionCreation.php
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

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Class TransactionCreation
 */
class TransactionCreation extends Notification
{
    use Queueable;

    private array $collection;


    public function __construct(array $collection)
    {
        $this->collection = $collection;
    }


    public function toArray($notifiable)
    {
        return [
        ];
    }


    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->markdown('emails.report-new-journals', ['transformed' => $this->collection])
            ->subject(trans_choice('email.new_journals_subject', count($this->collection)))
        ;
    }


    public function via($notifiable)
    {
        return ['mail'];
    }
}
