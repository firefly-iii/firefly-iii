<?php

/**
 * RegisteredUser.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);



/**
 * RegisteredUser.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

namespace FireflyIII\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegisteredUser extends Mailable
{
    use Queueable, SerializesModels;
    /** @var  string */
    public $address;
    /** @var  string */
    public $ipAddress;

    /**
     * Create a new message instance.
     *
     * @param string $address
     * @param string $ipAddress
     */
    public function __construct(string $address, string $ipAddress)
    {
        $this->address   = $address;
        $this->ipAddress = $ipAddress;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.registered-html')->text('emails.registered-text')->subject('Welcome to Firefly III!');
    }
}
