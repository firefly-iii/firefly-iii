<?php
/**
 * UserEventHandler.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
/** @noinspection NullPointerExceptionInspection */
declare(strict_types=1);

namespace FireflyIII\Handlers\Events;

use Exception;
use FireflyIII\Events\RegisteredUser;
use FireflyIII\Events\RequestedNewPassword;
use FireflyIII\Events\UserChangedEmail;
use FireflyIII\Mail\ConfirmEmailChangeMail;
use FireflyIII\Mail\RegisteredUser as RegisteredUserMail;
use FireflyIII\Mail\RequestedNewPassword as RequestedNewPasswordMail;
use FireflyIII\Mail\UndoEmailChangeMail;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Auth\Events\Login;
use Log;
use Mail;

/**
 * Class UserEventHandler.
 *
 * This class responds to any events that have anything to do with the User object.
 *
 * The method name reflects what is being done. This is in the present tense.
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
        if (1 === $repository->count()) {
            Log::debug('User count is one, attach role.');
            $repository->attachRole($event->user, 'owner');
        }

        return true;
    }

    /**
     * Fires to see if a user is admin.
     *
     * @param Login $event
     *
     * @return bool
     */
    public function checkSingleUserIsAdmin(Login $event): bool
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        /** @var User $user */
        $user  = $event->user;
        $count = $repository->count();

        // only act when there is 1 user in the system and he has no admin rights.
        if (1 === $count && !$repository->hasRole($user, 'owner')) {
            // user is the only user but does not have role "owner".
            $role = $repository->getRole('owner');
            if (null === $role) {
                // create role, does not exist. Very strange situation so let's raise a big fuss about it.
                $role = $repository->createRole('owner', 'Site Owner', 'User runs this instance of FF3');
                Log::error('Could not find role "owner". This is weird.');
            }

            Log::info(sprintf('Gave user #%d role #%d ("%s")', $user->id, $role->id, $role->name));
            // give user the role
            $repository->attachRole($user, 'owner');
        }

        return true;
    }

    /**
     * Set the demo user back to English.
     *
     * @param Login $event
     *
     * @return bool
     */
    public function demoUserBackToEnglish(Login $event): bool
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        /** @var User $user */
        $user = $event->user;
        if ($repository->hasRole($user, 'demo')) {
            // set user back to English.
            app('preferences')->setForUser($user, 'language', 'en_US');
            app('preferences')->mark();
        }

        return true;
    }

    /**
     * Send email to confirm email change.
     *
     * @param UserChangedEmail $event
     *
     * @return bool
     */
    public function sendEmailChangeConfirmMail(UserChangedEmail $event): bool
    {
        $newEmail  = $event->newEmail;
        $oldEmail  = $event->oldEmail;
        $user      = $event->user;
        $ipAddress = $event->ipAddress;
        $token     = app('preferences')->getForUser($user, 'email_change_confirm_token', 'invalid');
        $uri       = route('profile.confirm-email-change', [$token->data]);
        try {
            Mail::to($newEmail)->send(new ConfirmEmailChangeMail($newEmail, $oldEmail, $uri, $ipAddress));
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

        // @codeCoverageIgnoreEnd

        return true;
    }

    /**
     * Send email to be able to undo email change.
     *
     * @param UserChangedEmail $event
     *
     * @return bool
     */
    public function sendEmailChangeUndoMail(UserChangedEmail $event): bool
    {
        $newEmail  = $event->newEmail;
        $oldEmail  = $event->oldEmail;
        $user      = $event->user;
        $ipAddress = $event->ipAddress;
        $token     = app('preferences')->getForUser($user, 'email_change_undo_token', 'invalid');
        $uri       = route('profile.undo-email-change', [$token->data, hash('sha256', $oldEmail)]);
        try {
            Mail::to($oldEmail)->send(new UndoEmailChangeMail($newEmail, $oldEmail, $uri, $ipAddress));
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

        // @codeCoverageIgnoreEnd

        return true;
    }

    /**
     * Send a new password to the user.
     *
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
            Mail::to($email)->send(new RequestedNewPasswordMail($url, $ipAddress));
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

        // @codeCoverageIgnoreEnd

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
    public function sendRegistrationMail(RegisteredUser $event): bool
    {
        $sendMail = config('firefly.send_registration_mail');
        if ($sendMail) {
            // get the email address
            $email     = $event->user->email;
            $uri       = route('index');
            $ipAddress = $event->ipAddress;

            // send email.
            try {
                Mail::to($email)->send(new RegisteredUserMail($uri, $ipAddress));
                // @codeCoverageIgnoreStart
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
            // @codeCoverageIgnoreEnd
        }

        return true;
    }
}
