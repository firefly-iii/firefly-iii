<?php

namespace FireflyIII\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UndoEmailChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var  string */
    public $ipAddress;
    /** @var  string */
    public $newEmail;
    /** @var  string */
    public $oldEmail;
    /** @var  string */
    public $uri;

    /**
     * UndoEmailChangeMail constructor.
     *
     * @param string $newEmail
     * @param string $oldEmail
     * @param string $uri
     * @param string $ipAddress
     */
    public function __construct(string $newEmail, string $oldEmail, string $uri, string $ipAddress)
    {

        $this->newEmail  = $newEmail;
        $this->oldEmail  = $oldEmail;
        $this->uri       = $uri;
        $this->ipAddress = $ipAddress;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.undo-email-change-html')->text('emails.undo-email-change-text')
                    ->subject('Your Firefly III email address has changed.');
    }
}
