<?php

/**
 * UserEventHandler.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Handlers\Events;

use Exception;
use FireflyIII\Events\Admin\InvitationCreated;
use FireflyIII\Events\RequestedNewPassword;
use FireflyIII\Events\UserChangedEmail;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Mail\ConfirmEmailChangeMail;
use FireflyIII\Mail\InvitationMail;
use FireflyIII\Mail\UndoEmailChangeMail;
use FireflyIII\Notifications\User\UserNewPassword;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

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
     * Fires to see if a user is admin.
     */
    public function checkSingleUserIsAdmin(Login $event): void
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
    }


    /**
     * Set the demo user back to English.
     */
    public function demoUserBackToEnglish(Login $event): void
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        /** @var User $user */
        $user = $event->user;
        if ($repository->hasRole($user, 'demo')) {
            // set user back to English.
            Preferences::setForUser($user, 'language', 'en_US');
            Preferences::setForUser($user, 'locale', 'equal');
            Preferences::setForUser($user, 'anonymous', false);
            Preferences::mark();
        }
    }


    /**
     * Send email to confirm email change. Will not be made into a notification, because
     * this requires some custom fields from the user and not just the "user" object.
     *
     * @throws FireflyException
     */
    public function sendEmailChangeConfirmMail(UserChangedEmail $event): void
    {
        $newEmail = $event->newEmail;
        $oldEmail = $event->oldEmail;
        $user     = $event->user;
        $token    = Preferences::getForUser($user, 'email_change_confirm_token', 'invalid');
        $url      = route('profile.confirm-email-change', [$token->data]);

        try {
            Mail::to($newEmail)->send(new ConfirmEmailChangeMail($newEmail, $oldEmail, $url));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());

            throw new FireflyException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Send email to be able to undo email change. Will not be made into a notification, because
     * this requires some custom fields from the user and not just the "user" object.
     *
     * @throws FireflyException
     */
    public function sendEmailChangeUndoMail(UserChangedEmail $event): void
    {
        $newEmail = $event->newEmail;
        $oldEmail = $event->oldEmail;
        $user     = $event->user;
        $token    = Preferences::getForUser($user, 'email_change_undo_token', 'invalid');
        $hashed   = hash('sha256', sprintf('%s%s', (string)config('app.key'), $oldEmail));
        $url      = route('profile.undo-email-change', [$token->data, $hashed]);

        try {
            Mail::to($oldEmail)->send(new UndoEmailChangeMail($newEmail, $oldEmail, $url));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());

            throw new FireflyException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Send a new password to the user.
     */
    public function sendNewPassword(RequestedNewPassword $event): void
    {
        try {
            Notification::send($event->user, new UserNewPassword(route('password.reset', [$event->token])));
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'Bcc')) {
                Log::warning('[Bcc] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            if (str_contains($message, 'RFC 2822')) {
                Log::warning('[RFC] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }

    /**
     * @throws FireflyException
     */
    public function sendRegistrationInvite(InvitationCreated $event): void
    {
        $invitee = $event->invitee->email;
        $admin   = $event->invitee->user->email;
        $url     = route('invite', [$event->invitee->invite_code]);

        try {
            Mail::to($invitee)->send(new InvitationMail($invitee, $admin, $url));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());

            throw new FireflyException($e->getMessage(), 0, $e);
        }
    }


}
