<?php
/**
 * AdminTestMail.php
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

/**
 * Class AdminTestMail
 *
 * @package FireflyIII\Mail
 */
class AdminTestMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var  string */
    public $email;
    /** @var  string */
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
