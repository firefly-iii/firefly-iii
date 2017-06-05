<?php

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
    public $userIp;

    /**
     * Create a new message instance.
     *
     * @param string $address
     * @param string $userIp
     */
    public function __construct(string $address, string $userIp)
    {
        $this->address = $address;
        $this->userIp  = $userIp;
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
