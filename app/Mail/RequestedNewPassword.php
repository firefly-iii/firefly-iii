<?php

namespace FireflyIII\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestedNewPassword extends Mailable
{
    use Queueable, SerializesModels;
    /** @var  string */
    public $ip;
    /** @var  string */
    public $url;

    /**
     * RequestedNewPassword constructor.
     *
     * @param string $url
     * @param string $ip
     */
    public function __construct(string $url, string $ip)
    {
        $this->url = $url;
        $this->ip  = $ip;
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
