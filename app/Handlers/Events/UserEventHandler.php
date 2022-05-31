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
/** @noinspection NullPointerExceptionInspection */
declare(strict_types=1);

namespace FireflyIII\Handlers\Events;

use Carbon\Carbon;
use Exception;
use FireflyIII\Events\ActuallyLoggedIn;
use FireflyIII\Events\DetectedNewIPAddress;
use FireflyIII\Events\RegisteredUser;
use FireflyIII\Events\RequestedNewPassword;
use FireflyIII\Events\UserChangedEmail;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Mail\ConfirmEmailChangeMail;
use FireflyIII\Mail\NewIPAddressWarningMail;
use FireflyIII\Mail\RegisteredUser as RegisteredUserMail;
use FireflyIII\Mail\RequestedNewPassword as RequestedNewPasswordMail;
use FireflyIII\Mail\UndoEmailChangeMail;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\Models\UserRole;
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
     * @param RegisteredUser $event
     *
     * @return bool
     * @throws FireflyException
     */
    public function createGroupMembership(RegisteredUser $event): bool
    {
        $user        = $event->user;
        $groupExists = true;
        $groupTitle  = $user->email;
        $index       = 1;

        // create a new group.
        while (true === $groupExists) {
            $groupExists = UserGroup::where('title', $groupTitle)->count() > 0;
            if (false === $groupExists) {
                $group = UserGroup::create(['title' => $groupTitle]);
                break;
            }
            $groupTitle = sprintf('%s-%d', $user->email, $index);
            $index++;
            if ($index > 99) {
                throw new FireflyException('Email address can no longer be used for registrations.');
            }
        }
        $role = UserRole::where('title', UserRole::OWNER)->first();
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

        return true;
    }

    /**
     * Set the demo user back to English.
     *
     * @param Login $event
     *
     * @return bool
     * @throws FireflyException
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
            app('preferences')->setForUser($user, 'locale', 'equal');
            app('preferences')->mark();
        }

        return true;
    }

    /**
     * @param DetectedNewIPAddress $event
     *
     * @throws FireflyException
     */
    public function notifyNewIPAddress(DetectedNewIPAddress $event): void
    {
        $user      = $event->user;
        $email     = $user->email;
        $ipAddress = $event->ipAddress;

        if ($user->hasRole('demo')) {
            return; // do not email demo user.
        }

        $list = app('preferences')->getForUser($user, 'login_ip_history', [])->data;

        // see if user has alternative email address:
        $pref = app('preferences')->getForUser($user, 'remote_guard_alt_email');
        if (null !== $pref) {
            $email = $pref->data;
        }

        /** @var array $entry */
        foreach ($list as $index => $entry) {
            if (false === $entry['notified']) {
                try {
                    Mail::to($email)->send(new NewIPAddressWarningMail($ipAddress));

                } catch (Exception $e) { // @phpstan-ignore-line
                    Log::error($e->getMessage());
                }
            }
            $list[$index]['notified'] = true;
        }

        app('preferences')->setForUser($user, 'login_ip_history', $list);
    }

    /**
     * Send email to confirm email change.
     *
     * @param UserChangedEmail $event
     *
     * @return bool
     * @throws FireflyException
     */
    public function sendEmailChangeConfirmMail(UserChangedEmail $event): bool
    {
        $newEmail = $event->newEmail;
        $oldEmail = $event->oldEmail;
        $user     = $event->user;
        $token    = app('preferences')->getForUser($user, 'email_change_confirm_token', 'invalid');
        $url      = route('profile.confirm-email-change', [$token->data]);
        try {
            Mail::to($newEmail)->send(new ConfirmEmailChangeMail($newEmail, $oldEmail, $url));

        } catch (Exception $e) { // @phpstan-ignore-line
            Log::error($e->getMessage());
        }

        return true;
    }

    /**
     * Send email to be able to undo email change.
     *
     * @param UserChangedEmail $event
     *
     * @return bool
     * @throws FireflyException
     */
    public function sendEmailChangeUndoMail(UserChangedEmail $event): bool
    {
        $newEmail = $event->newEmail;
        $oldEmail = $event->oldEmail;
        $user     = $event->user;
        $token    = app('preferences')->getForUser($user, 'email_change_undo_token', 'invalid');
        $hashed   = hash('sha256', sprintf('%s%s', (string) config('app.key'), $oldEmail));
        $url      = route('profile.undo-email-change', [$token->data, $hashed]);
        try {
            Mail::to($oldEmail)->send(new UndoEmailChangeMail($newEmail, $oldEmail, $url));

        } catch (Exception $e) { // @phpstan-ignore-line
            Log::error($e->getMessage());
        }

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

        } catch (Exception $e) { // @phpstan-ignore-line
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
     * @throws FireflyException
     */
    public function sendRegistrationMail(RegisteredUser $event): bool
    {
        $sendMail = config('firefly.send_registration_mail');
        if ($sendMail) {
            // get the email address
            $email     = $event->user->email;
            $url       = route('index');

            // see if user has alternative email address:
            $pref = app('preferences')->getForUser($event->user, 'remote_guard_alt_email');
            if (null !== $pref) {
                $email = $pref->data;
            }

            // send email.
            try {
                Mail::to($email)->send(new RegisteredUserMail($url));

            } catch (Exception $e) { // @phpstan-ignore-line
                Log::error($e->getMessage());
            }

        }

        return true;
    }

    /**
     * @param ActuallyLoggedIn $event
     * @throws FireflyException
     */
    public function storeUserIPAddress(ActuallyLoggedIn $event): void
    {
        Log::debug('Now in storeUserIPAddress');
        $user = $event->user;
        /** @var array $preference */

        if($user->hasRole('demo')) {
            Log::debug('Do not log demo user logins');
            return;
        }

        try {
            $preference = app('preferences')->getForUser($user, 'login_ip_history', [])->data;
        } catch (FireflyException $e) {
            // don't care.
            Log::error($e->getMessage());

            return;
        }
        $inArray = false;
        $ip      = request()->ip();
        Log::debug(sprintf('User logging in from IP address %s', $ip));

        // update array if in array
        foreach ($preference as $index => $row) {
            if ($row['ip'] === $ip) {
                Log::debug('Found IP in array, refresh time.');
                $preference[$index]['time'] = now(config('app.timezone'))->format('Y-m-d H:i:s');
                $inArray                    = true;
            }
            // clean up old entries (6 months)
            $carbon = Carbon::createFromFormat('Y-m-d H:i:s', $preference[$index]['time']);
            if ($carbon->diffInMonths(today()) > 6) {
                Log::debug(sprintf('Entry for %s is very old, remove it.', $row['ip']));
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
        app('preferences')->setForUser($user, 'login_ip_history', $preference);

        if (false === $inArray && true === config('firefly.warn_new_ip')) {
            event(new DetectedNewIPAddress($user, $ip));
        }

    }
}
