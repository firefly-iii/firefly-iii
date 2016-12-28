<?php
/**
 * UserEventHandler.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\RegisteredUser;
use FireflyIII\Events\RequestedNewPassword;
use FireflyIII\Events\ResentConfirmation;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Events\SendUserMail;
use Illuminate\Mail\Message;
use Log;
use Mail;
use Session;
use Swift_TransportException;

/**
 * Class UserEventHandler
 *
 * This class responds to any events that have anything to do with the User object.
 *
 * The method name reflects what is being done. This is in the present tense.
 *
 * @package FireflyIII\Handlers\Events
 */
class UserEventHandler
{

    /**
     * This method will bestow upon a user the "owner" role if he is the first user in the system.
     *
     * @param RegisteredUser $event
     *
     * @return bool
     */
    public function attachUserRole(RegisteredUser $event): bool
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        // first user ever?
        if ($repository->count() === 1) {
            $repository->attachRole($event->user, 'owner');
        }

        return true;
    }

    /**
     * Handle user logout events.
     *
     * @return bool
     */
    public function logoutUser(): bool
    {
        // dump stuff from the session:
        Session::forget('twofactor-authenticated');
        Session::forget('twofactor-authenticated-date');

        return true;
    }

    /**
     * This method will send a newly registered user a confirmation message, urging him or her to activate their account.
     *
     * @param RegisteredUser $event
     *
     * @return bool
     */
    public function sendConfirmationMessage(RegisteredUser $event): bool
    {
        $sender = new SendUserMail;

        return $sender->sendConfirmation($event->user, $event->ipAddress);
    }

    /**
     * If the user has somehow lost his or her confirmation message, this event will send it to the user again.
     *
     * At the moment, this method is exactly the same as the ::sendConfirmationMessage method, but that will change.
     *
     * @param ResentConfirmation $event
     *
     * @return bool
     */
    function sendConfirmationMessageAgain(ResentConfirmation $event): bool
    {
        $sender = new SendUserMail;

        return $sender->sendConfirmation($event->user, $event->ipAddress);

    }

    /**
     * @param RequestedNewPassword $event
     *
     * @return bool
     */
    public function sendNewPassword(RequestedNewPassword $event): bool
    {
        $email     = $event->user->email;
        $ipAddress = $event->ipAddress;
        $token     = $event->token;

        $url = route('password.reset', [$token]);

        // send email.
        try {
            Mail::send(
                ['emails.password-html', 'emails.password-text'], ['url' => $url, 'ip' => $ipAddress], function (Message $message) use ($email) {
                $message->to($email, $email)->subject('Your password reset request');
            }
            );
        } catch (Swift_TransportException $e) {
            Log::error($e->getMessage());
        }

        return true;
    }

    /**
     * This method will send the user a registration mail, welcoming him or her to Firefly III.
     * This message is only sent when the configuration of Firefly III says so.
     *
     * @param RegisteredUser $event
     *
     * @return bool
     */
    public function sendRegistrationMail(RegisteredUser $event)
    {

        $sendMail = env('SEND_REGISTRATION_MAIL', true);
        if (!$sendMail) {
            return true;
        }
        // get the email address
        $email     = $event->user->email;
        $address   = route('index');
        $ipAddress = $event->ipAddress;
        // send email.
        try {
            Mail::send(
                ['emails.registered-html', 'emails.registered-text'], ['address' => $address, 'ip' => $ipAddress], function (Message $message) use ($email) {
                $message->to($email, $email)->subject('Welcome to Firefly III!');
            }
            );
        } catch (Swift_TransportException $e) {
            Log::error($e->getMessage());
        }

        return true;
    }
}
