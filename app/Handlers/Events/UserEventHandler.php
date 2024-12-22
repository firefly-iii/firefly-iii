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

use Carbon\Carbon;
use Database\Seeders\ExchangeRateSeeder;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Events\ActuallyLoggedIn;
use FireflyIII\Events\Admin\InvitationCreated;
use FireflyIII\Events\DetectedNewIPAddress;
use FireflyIII\Events\RegisteredUser;
use FireflyIII\Events\RequestedNewPassword;
use FireflyIII\Events\Security\UserAttemptedLogin;
use FireflyIII\Events\Test\UserTestNotificationChannel;
use FireflyIII\Events\UserChangedEmail;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Mail\ConfirmEmailChangeMail;
use FireflyIII\Mail\InvitationMail;
use FireflyIII\Mail\UndoEmailChangeMail;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\Models\UserRole;
use FireflyIII\Notifications\Admin\UserRegistration as AdminRegistrationNotification;
use FireflyIII\Notifications\Security\UserFailedLoginAttempt;
use FireflyIII\Notifications\Test\UserTestNotificationEmail;
use FireflyIII\Notifications\Test\UserTestNotificationNtfy;
use FireflyIII\Notifications\Test\UserTestNotificationPushover;
use FireflyIII\Notifications\Test\UserTestNotificationSlack;
use FireflyIII\Notifications\User\UserLogin;
use FireflyIII\Notifications\User\UserNewPassword;
use FireflyIII\Notifications\User\UserRegistration as UserRegistrationNotification;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
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
     */
    public function attachUserRole(RegisteredUser $event): void
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        // first user ever?
        if (1 === $repository->count()) {
            app('log')->debug('User count is one, attach role.');
            $repository->attachRole($event->user, 'owner');
        }
    }

    /**
     * Fires to see if a user is admin.
     */
    public function checkSingleUserIsAdmin(Login $event): void
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        /** @var User $user */
        $user       = $event->user;
        $count      = $repository->count();

        // only act when there is 1 user in the system and he has no admin rights.
        if (1 === $count && !$repository->hasRole($user, 'owner')) {
            // user is the only user but does not have role "owner".
            $role = $repository->getRole('owner');
            if (null === $role) {
                // create role, does not exist. Very strange situation so let's raise a big fuss about it.
                $role = $repository->createRole('owner', 'Site Owner', 'User runs this instance of FF3');
                app('log')->error('Could not find role "owner". This is weird.');
            }

            app('log')->info(sprintf('Gave user #%d role #%d ("%s")', $user->id, $role->id, $role->name));
            // give user the role
            $repository->attachRole($user, 'owner');
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createExchangeRates(RegisteredUser $event): void
    {
        $seeder = new ExchangeRateSeeder();
        $seeder->run();
    }

    /**
     * @throws FireflyException
     */
    public function createGroupMembership(RegisteredUser $event): void
    {
        $user                = $event->user;
        $groupExists         = true;
        $groupTitle          = $user->email;
        $index               = 1;

        /** @var UserGroup $group */
        $group               = null;

        // create a new group.
        while (true === $groupExists) { // @phpstan-ignore-line
            $groupExists = UserGroup::where('title', $groupTitle)->count() > 0;
            if (false === $groupExists) {
                $group = UserGroup::create(['title' => $groupTitle]);

                break;
            }
            $groupTitle  = sprintf('%s-%d', $user->email, $index);
            ++$index;
            if ($index > 99) {
                throw new FireflyException('Email address can no longer be used for registrations.');
            }
        }

        /** @var null|UserRole $role */
        $role                = UserRole::where('title', UserRoleEnum::OWNER->value)->first();
        if (null === $role) {
            throw new FireflyException('The user role is unexpectedly empty. Did you run all migrations?');
        }
        GroupMembership::create(
            [
                'user_id'       => $user->id,
                'user_group_id' => $group->id,
                'user_role_id'  => $role->id,
            ]
        );
        $user->user_group_id = $group->id;
        $user->save();
    }

    /**
     * Set the demo user back to English.
     *
     * @throws FireflyException
     */
    public function demoUserBackToEnglish(Login $event): void
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        /** @var User $user */
        $user       = $event->user;
        if ($repository->hasRole($user, 'demo')) {
            // set user back to English.
            app('preferences')->setForUser($user, 'language', 'en_US');
            app('preferences')->setForUser($user, 'locale', 'equal');
            app('preferences')->mark();
        }
    }

    /**
     * @throws FireflyException
     */
    public function notifyNewIPAddress(DetectedNewIPAddress $event): void
    {
        $user = $event->user;

        if ($user->hasRole('demo')) {
            return; // do not email demo user.
        }

        $list = app('preferences')->getForUser($user, 'login_ip_history', [])->data;
        if (!is_array($list)) {
            $list = [];
        }

        /** @var array $entry */
        foreach ($list as $index => $entry) {
            if (false === $entry['notified']) {
                try {
                    Notification::send($user, new UserLogin());
                } catch (\Exception $e) { // @phpstan-ignore-line
                    $message = $e->getMessage();
                    if (str_contains($message, 'Bcc')) {
                        app('log')->warning('[Bcc] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                        return;
                    }
                    if (str_contains($message, 'RFC 2822')) {
                        app('log')->warning('[RFC] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                        return;
                    }
                    app('log')->error($e->getMessage());
                    app('log')->error($e->getTraceAsString());
                }
            }
            $list[$index]['notified'] = true;
        }

        app('preferences')->setForUser($user, 'login_ip_history', $list);
    }

    public function sendAdminRegistrationNotification(RegisteredUser $event): void
    {
        $sendMail = (bool) app('fireflyconfig')->get('notification_admin_new_reg', true)->data;
        if ($sendMail) {
            $owner = $event->owner;

            try {
                Notification::send($owner, new AdminRegistrationNotification($event->owner, $event->user));
            } catch (\Exception $e) { // @phpstan-ignore-line
                $message = $e->getMessage();
                if (str_contains($message, 'Bcc')) {
                    app('log')->warning('[Bcc] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                    return;
                }
                if (str_contains($message, 'RFC 2822')) {
                    app('log')->warning('[RFC] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                    return;
                }
                app('log')->error($e->getMessage());
                app('log')->error($e->getTraceAsString());
            }
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
        $token    = app('preferences')->getForUser($user, 'email_change_confirm_token', 'invalid');
        $url      = route('profile.confirm-email-change', [$token->data]);

        try {
            \Mail::to($newEmail)->send(new ConfirmEmailChangeMail($newEmail, $oldEmail, $url));
        } catch (\Exception $e) {
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());

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
        $token    = app('preferences')->getForUser($user, 'email_change_undo_token', 'invalid');
        $hashed   = hash('sha256', sprintf('%s%s', (string) config('app.key'), $oldEmail));
        $url      = route('profile.undo-email-change', [$token->data, $hashed]);

        try {
            \Mail::to($oldEmail)->send(new UndoEmailChangeMail($newEmail, $oldEmail, $url));
        } catch (\Exception $e) {
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());

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
        } catch (\Exception $e) { // @phpstan-ignore-line
            $message = $e->getMessage();
            if (str_contains($message, 'Bcc')) {
                app('log')->warning('[Bcc] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            if (str_contains($message, 'RFC 2822')) {
                app('log')->warning('[RFC] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
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
            \Mail::to($invitee)->send(new InvitationMail($invitee, $admin, $url));
        } catch (\Exception $e) {
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());

            throw new FireflyException($e->getMessage(), 0, $e);
        }
    }

    /**
     * This method will send the user a registration mail, welcoming him or her to Firefly III.
     * This message is only sent when the configuration of Firefly III says so.
     */
    public function sendRegistrationMail(RegisteredUser $event): void
    {
        $sendMail = (bool) app('fireflyconfig')->get('notification_user_new_reg', true)->data;
        if ($sendMail) {
            try {
                Notification::send($event->user, new UserRegistrationNotification());
            } catch (\Exception $e) { // @phpstan-ignore-line
                $message = $e->getMessage();
                if (str_contains($message, 'Bcc')) {
                    app('log')->warning('[Bcc] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                    return;
                }
                if (str_contains($message, 'RFC 2822')) {
                    app('log')->warning('[RFC] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                    return;
                }
                app('log')->error($e->getMessage());
                app('log')->error($e->getTraceAsString());
            }
        }
    }

    /**
     * @throws FireflyException
     */
    public function storeUserIPAddress(ActuallyLoggedIn $event): void
    {
        app('log')->debug('Now in storeUserIPAddress');
        $user       = $event->user;

        if ($user->hasRole('demo')) {
            app('log')->debug('Do not log demo user logins');

            return;
        }

        try {
            /** @var array $preference */
            $preference = app('preferences')->getForUser($user, 'login_ip_history', [])->data;
        } catch (FireflyException $e) {
            // don't care.
            app('log')->error($e->getMessage());

            return;
        }
        $inArray    = false;
        $ip         = request()->ip();
        app('log')->debug(sprintf('User logging in from IP address %s', $ip));

        // update array if in array
        foreach ($preference as $index => $row) {
            if ($row['ip'] === $ip) {
                app('log')->debug('Found IP in array, refresh time.');
                $preference[$index]['time'] = now(config('app.timezone'))->format('Y-m-d H:i:s');
                $inArray                    = true;
            }
            // clean up old entries (6 months)
            $carbon = Carbon::createFromFormat('Y-m-d H:i:s', $preference[$index]['time']);
            if (null !== $carbon && $carbon->diffInMonths(today(), true) > 6) {
                app('log')->debug(sprintf('Entry for %s is very old, remove it.', $row['ip']));
                unset($preference[$index]);
            }
        }
        // add to array if not the case:
        if (false === $inArray) {
            $preference[] = [
                'ip'       => $ip,
                'time'     => now(config('app.timezone'))->format('Y-m-d H:i:s'),
                'notified' => false,
            ];
        }
        $preference = array_values($preference);

        /** @var bool $send */
        $send       = app('preferences')->getForUser($user, 'notification_user_login', true)->data;
        app('preferences')->setForUser($user, 'login_ip_history', $preference);

        if (false === $inArray && true === $send) {
            event(new DetectedNewIPAddress($user));
        }
    }

    public function sendLoginAttemptNotification(UserAttemptedLogin $event): void
    {
        try {
            Notification::send($event->user, new UserFailedLoginAttempt($event->user));
        } catch (\Exception $e) { // @phpstan-ignore-line
            $message = $e->getMessage();
            if (str_contains($message, 'Bcc')) {
                app('log')->warning('[Bcc] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            if (str_contains($message, 'RFC 2822')) {
                app('log')->warning('[RFC] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
        }
    }

    /**
     * Sends a test message to an administrator.
     */
    public function sendTestNotification(UserTestNotificationChannel $event): void
    {
        Log::debug(sprintf('Now in (user) sendTestNotification("%s")', $event->channel));

        switch ($event->channel) {
            case 'email':
                $class = UserTestNotificationEmail::class;

                break;

            case 'slack':
                $class = UserTestNotificationSlack::class;

                break;

            case 'ntfy':
                $class = UserTestNotificationNtfy::class;

                break;

            case 'pushover':
                $class = UserTestNotificationPushover::class;

                break;

            default:
                app('log')->error(sprintf('Unknown channel "%s" in (user) sendTestNotification method.', $event->channel));

                return;
        }
        Log::debug(sprintf('Will send %s as a notification.', $class));

        try {
            Notification::send($event->user, new $class($event->user));
        } catch (\Exception $e) { // @phpstan-ignore-line
            $message = $e->getMessage();
            if (str_contains($message, 'Bcc')) {
                app('log')->warning('[Bcc] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            if (str_contains($message, 'RFC 2822')) {
                app('log')->warning('[RFC] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
        }
        Log::debug(sprintf('If you see no errors above this line, test notification was sent over channel "%s"', $event->channel));
    }
}
