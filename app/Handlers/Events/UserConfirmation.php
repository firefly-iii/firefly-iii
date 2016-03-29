<?php
/**
 * UserConfirmation.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Handlers\Events;


use FireflyIII\Events\ResendConfirmation;
use FireflyIII\Events\UserRegistration;
use FireflyIII\User;
use Illuminate\Mail\Message;
use Log;
use Mail;
use Preferences;
use Swift_TransportException;

/**
 * Class UserConfirmation
 *
 * @package FireflyIII\Handlers\Events
 */
class UserConfirmation
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
     * @param ResendConfirmation $event
     */
    public function resendConfirmation(ResendConfirmation $event)
    {
        $user      = $event->user;
        $ipAddress = $event->ip;
        $this->doConfirm($user, $ipAddress);
    }

    /**
     * Handle the event.
     *
     * @param  UserRegistration $event
     */
    public function sendConfirmation(UserRegistration $event)
    {
        $user      = $event->user;
        $ipAddress = $event->ip;
        $this->doConfirm($user, $ipAddress);
    }

    /**
     * @param User   $user
     * @param string $ipAddress
     */
    private function doConfirm(User $user, string $ipAddress)
    {
        Log::debug('Trigger UserConfirmation::doConfirm');

        // if user must confirm account, send email
        $confirmAccount = env('MUST_CONFIRM_ACCOUNT', false);

        // otherwise, auto-confirm:
        if ($confirmAccount === false) {
            Preferences::setForUser($user, 'user_confirmed', true);
            Preferences::setForUser($user, 'user_confirmed_last_mail', 0);

            return;
        }

        // send email message:
        $email = $user->email;
        $code  = str_random(16);
        $route = route('do_confirm_account', [$code]);

        // set preferences:
        Preferences::setForUser($user, 'user_confirmed', false);
        Preferences::setForUser($user, 'user_confirmed_last_mail', time());
        Preferences::setForUser($user, 'user_confirmed_code', $code);

        // send email.
        try {
            Mail::send(
                ['emails.confirm-account-html', 'emails.confirm-account'], ['route' => $route, 'ip' => $ipAddress],
                function (Message $message) use ($email) {
                    $message->to($email, $email)->subject('Please confirm your Firefly III account');
                }
            );
        } catch (Swift_TransportException $e) {
            Log::error($e->getMessage());
        }
    }

}