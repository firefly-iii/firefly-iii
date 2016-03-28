<?php
/**
 * SendRegistrationMail.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Handlers\Events;


use FireflyIII\Events\UserRegistration;
use Illuminate\Mail\Message;
use Log;
use Mail;
use Swift_TransportException;

/**
 * Class SendRegistrationMail
 *
 * @package FireflyIII\Handlers\Events
 */
class SendRegistrationMail
{
    /**
     * Create the event listener.
     *
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UserRegistration $event
     *
     * @return void
     */
    public function handle(UserRegistration $event)
    {
        $sendMail = env('SEND_REGISTRATION_MAIL', true);
        if (!$sendMail) {
            return;
        }
        // get the email address
        $email     = $event->user->email;
        $address   = route('index');
        $ipAddress = $event->ip;
        // send email.
        try {
            Mail::send(
                ['emails.registered-html', 'emails.registered'], ['address' => $address, 'ip' => $ipAddress], function (Message $message) use ($email) {
                $message->to($email, $email)->subject('Welcome to Firefly III! ');
            }
            );
        } catch (Swift_TransportException $e) {
            Log::error($e->getMessage());
        }
    }
}