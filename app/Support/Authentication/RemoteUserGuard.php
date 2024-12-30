<?php

/**
 * RemoteUserGuard.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Support\Authentication;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

/**
 * Class RemoteUserGuard
 */
class RemoteUserGuard implements Guard
{
    protected Application  $application;
    protected UserProvider $provider;
    protected ?User        $user;

    /**
     * Create a new authentication guard.
     */
    public function __construct(UserProvider $provider, Application $app)
    {
        /** @var null|Request $request */
        $request           = $app->get('request');
        app('log')->debug(sprintf('Created RemoteUserGuard for %s "%s"', $request?->getMethod(), $request?->getRequestUri()));
        $this->application = $app;
        $this->provider    = $provider;
        $this->user        = null;
    }

    public function authenticate(): void
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        if (null !== $this->user) {
            app('log')->debug(sprintf('%s is found: #%d, "%s".', get_class($this->user), $this->user->id, $this->user->email));

            return;
        }
        // Get the user identifier from $_SERVER or apache filtered headers
        $header        = config('auth.guard_header', 'REMOTE_USER');
        $userID        = request()->server($header) ?? null;

        if (function_exists('apache_request_headers')) {
            app('log')->debug('Use apache_request_headers to find user ID.');
            $userID = request()->server($header) ?? apache_request_headers()[$header] ?? null;
        }

        if (null === $userID || '' === $userID) {
            app('log')->error(sprintf('No user in header "%s".', $header));

            throw new FireflyException('The guard header was unexpectedly empty. See the logs.');
        }

        app('log')->debug(sprintf('User ID found in header is "%s"', $userID));

        /** @var User $retrievedUser */
        $retrievedUser = $this->provider->retrieveById($userID);

        // store email address if present in header and not already set.
        $header        = config('auth.guard_email');

        if (null !== $header) {
            $emailAddress = (string) (request()->server($header) ?? apache_request_headers()[$header] ?? null);
            $preference   = app('preferences')->getForUser($retrievedUser, 'remote_guard_alt_email');

            if ('' !== $emailAddress && null === $preference && $emailAddress !== $userID) {
                app('preferences')->setForUser($retrievedUser, 'remote_guard_alt_email', $emailAddress);
            }
            // if the pref isn't null and the object returned isn't null, update the email address.
            if ('' !== $emailAddress && null !== $preference && $emailAddress !== $preference->data) {
                app('preferences')->setForUser($retrievedUser, 'remote_guard_alt_email', $emailAddress);
            }
        }

        app('log')->debug(sprintf('Result of getting user from provider: %s', $retrievedUser->email));
        $this->user    = $retrievedUser;
    }

    public function guest(): bool
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));

        return !$this->check();
    }

    public function check(): bool
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));

        return null !== $this->user();
    }

    public function user(): ?User
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        $user = $this->user;
        if (null === $user) {
            app('log')->debug('User is NULL');

            return null;
        }

        return $user;
    }

    public function hasUser(): bool
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));

        throw new FireflyException('Did not implement RemoteUserGuard::hasUser()');
    }

    /**
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function id(): null|int|string
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));

        return $this->user?->id;
    }

    public function setUser(null|Authenticatable|User $user): void // @phpstan-ignore-line
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        if ($user instanceof User) {
            $this->user = $user;

            return;
        }
        app('log')->error(sprintf('Did not set user at %s', __METHOD__));
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(array $credentials = []): bool
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));

        throw new FireflyException('Did not implement RemoteUserGuard::validate()');
    }

    public function viaRemember(): bool
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));

        return false;
    }
}
