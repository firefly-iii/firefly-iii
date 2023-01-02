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
use Log;

/**
 * Class RemoteUserGuard
 */
class RemoteUserGuard implements Guard
{
    protected Application $application;
    protected $provider;
    protected $user;

    /**
     * Create a new authentication guard.
     *
     * @param  UserProvider  $provider
     * @param  Application  $app
     */
    // @phpstan-ignore-next-line
    public function __construct(UserProvider $provider, Application $app) // @phpstan-ignore-line
    {
        /** @var Request $request */
        $request = $app->get('request');
        Log::debug(sprintf('Created RemoteUserGuard for "%s"', $request?->getRequestUri()));
        $this->application = $app;
        $this->provider    = $provider;
        $this->user        = null;
    }

    /**
     * @return bool
     */
    public function viaRemember(): bool
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        return false;
    }

    /**
     *
     */
    public function authenticate(): void
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        if (null !== $this->user) {
            Log::debug(sprintf('%s is found: #%d, "%s".', get_class($this->user), $this->user->id, $this->user->email));

            return;
        }
        // Get the user identifier from $_SERVER or apache filtered headers
        $header = config('auth.guard_header', 'REMOTE_USER');
        $userID = request()->server($header) ?? null;
        if (function_exists('apache_request_headers')) {
            $userID = request()->server($header) ?? apache_request_headers()[$header] ?? null;
        }
        if (null === $userID) {
            Log::error(sprintf('No user in header "%s".', $header));
            throw new FireflyException('The guard header was unexpectedly empty. See the logs.');
        }

        /** @var User $retrievedUser */
        $retrievedUser = $this->provider->retrieveById($userID);

        // store email address if present in header and not already set.
        $header = config('auth.guard_email');

        if (null !== $header) {
            $emailAddress = (string)(request()->server($header) ?? apache_request_headers()[$header] ?? null);
            $preference   = app('preferences')->getForUser($retrievedUser, 'remote_guard_alt_email');

            if ('' !== $emailAddress && null === $preference && $emailAddress !== $userID) {
                app('preferences')->setForUser($retrievedUser, 'remote_guard_alt_email', $emailAddress);
            }
            // if the pref isn't null and the object returned isn't null, update the email address.
            if ('' !== $emailAddress && null !== $preference && $emailAddress !== $preference->data) {
                app('preferences')->setForUser($retrievedUser, 'remote_guard_alt_email', $emailAddress);
            }
        }

        Log::debug(sprintf('Result of getting user from provider: %s', $retrievedUser->email));
        $this->user = $retrievedUser;
    }

    /**
     * @inheritDoc
     */
    public function guest(): bool
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        return !$this->check();
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        return !is_null($this->user());
    }

    /**
     * @inheritDoc
     */
    public function user(): ?User
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        //$this->authenticate();
        return $this->user;
    }

    /**
     * @inheritDoc
     */
    public function hasUser()
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        // TODO: Implement hasUser() method.
    }

    /**
     * @inheritDoc
     */
    public function id(): ?User
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        return $this->user;
    }

    /**
     * @inheritDoc
     */
    public function setUser(Authenticatable $user)
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        $this->user = $user;
    }

    /**
     * @inheritDoc
     */
    public function validate(array $credentials = [])
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        throw new FireflyException('Did not implement RemoteUserGuard::validate()');
    }
}
