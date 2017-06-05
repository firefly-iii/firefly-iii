<?php

namespace FireflyIII\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestedNewPassword extends Mailable
{
    use Queueable, SerializesModels;
    /** @var  string */
    public $url;
    /** @var  string */
    public $userIp;

    /**
     * RequestedNewPassword constructor.
     *
     * @param string $url
     * @param string $userIp
     */
    public function __construct(string $url, string $userIp)
    {
        $this->url    = $url;
        $this->userIp = $userIp;
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
