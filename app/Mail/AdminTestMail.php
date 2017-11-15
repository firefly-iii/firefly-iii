<?php

declare(strict_types=1);

/**
 * AdminTestMail.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FireflyIII\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class AdminTestMail.
 */
class AdminTestMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var string */
    public $email;
    /** @var string */
    public $ipAddress;

    /**
     * ConfirmEmailChangeMail constructor.
     *
     * @param string $email
     * @param string $ipAddress
     */
    public function __construct(string $email, string $ipAddress)
    {
        $this->email     = $email;
        $this->ipAddress = $ipAddress;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.admin-test-html')->text('emails.admin-test-text')
                    ->subject('A test message from your Firefly III installation');
    }
}
