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
use Log;


/**
 * Class RemoteUserGuard
 */
class RemoteUserGuard implements Guard
{
    protected Application $application;
    protected             $provider;
    protected             $user;

    /**
     * Create a new authentication guard.
     *
     * @param \Illuminate\Contracts\Auth\UserProvider $provider
     *
     * @return void
     */
    public function __construct(UserProvider $provider, Application $app)
    {
        $this->application = $app;
        $this->provider    = $provider;
        $this->user        = null;
    }

    /**
     *
     */
    public function authenticate(): void
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        if (!is_null($this->user)) {
            Log::debug('No user found.');

            return;
        }
        // Get the user identifier from $_SERVER
        $header = config('auth.guard_header', 'REMOTE_USER');
        $userID = request()->server($header) ?? null;
        if (null === $userID) {
            Log::error(sprintf('No user in header "%s".', $header));
            throw new FireflyException('The guard header was unexpectedly empty. See the logs.');
        }

        /** @var User $user */
        $user = $this->provider->retrieveById($userID);

        Log::debug(sprintf('Result of getting user from provider: %s', $user->email));
        $this->user = $user;
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        $result = !is_null($this->user());
        Log::debug(sprintf('Now in check(). Will return %s', var_export($result, true)));

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * @inheritDoc
     */
    public function id(): ?User
    {
        return $this->user;
    }

    /**
     * @inheritDoc
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }

    /**
     * @inheritDoc
     */
    public function user(): ?User
    {
        return $this->user;
    }

    /**
     * @inheritDoc
     */
    public function validate(array $credentials = [])
    {
        throw new FireflyException('Did not implement RemoteUserGuard::validate()');
    }
}
