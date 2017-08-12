<?php


/**
 * RequestedNewPassword.php
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

class RequestedNewPassword extends Mailable
{
    use Queueable, SerializesModels;
    /** @var  string */
    public $ipAddress;
    /** @var  string */
    public $url;

    /**
     * RequestedNewPassword constructor.
     *
     * @param string $url
     * @param string $ipAddress
     */
    public function __construct(string $url, string $ipAddress)
    {
        $this->url       = $url;
        $this->ipAddress = $ipAddress;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.password-html')->text('emails.password-text')->subject('Your password reset request');
    }
}
