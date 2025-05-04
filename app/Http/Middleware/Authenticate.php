<?php

/**
 * Authenticate.php
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

namespace FireflyIII\Http\Middleware;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;

/**
 * Class Authenticate
 */
class Authenticate
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        /**
         * The authentication factory instance.
         */
        protected Auth $auth
    )
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param Request  $request
     * @param string[] ...$guards
     *
     * @return mixed
     *
     * @throws FireflyException
     * @throws AuthenticationException
     */
    public function handle($request, \Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        return $next($request);
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @param mixed $request
     *
     * @return mixed
     *
     * @throws FireflyException
     * @throws AuthenticationException
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    protected function authenticate($request, array $guards)
    {
        if (0 === count($guards)) {
            // go for default guard:
            // @noinspection PhpUndefinedMethodInspection
            if ($this->auth->check()) {
                // do an extra check on user object.
                /** @noinspection PhpUndefinedMethodInspection */
                /** @var User $user */
                $user = $this->auth->authenticate();
                $this->validateBlockedUser($user, $guards);
            }

            // @noinspection PhpUndefinedMethodInspection
            return $this->auth->authenticate();
        }

        foreach ($guards as $guard) {
            if ('api' !== $guard) {
                $this->auth->guard($guard)->authenticate();
            }
            $result = $this->auth->guard($guard)->check();
            if ($result) {
                $user = $this->auth->guard($guard)->user();
                $this->validateBlockedUser($user, $guards);

                // According to PHPstan the method returns void, but we'll see.
                return $this->auth->shouldUse($guard); // @phpstan-ignore-line
            }
        }

        throw new AuthenticationException('Unauthenticated.', $guards);
    }

    /**
     * @throws AuthenticationException
     */
    private function validateBlockedUser(?User $user, array $guards): void
    {
        if (null === $user) {
            app('log')->warning('User is null, throw exception?');
        }
        if (null !== $user) {
            // app('log')->debug(get_class($user));
            if (1 === (int) $user->blocked) {
                $message = (string) trans('firefly.block_account_logout');
                if ('email_changed' === $user->blocked_code) {
                    $message = (string) trans('firefly.email_changed_logout');
                }
                app('log')->warning('User is blocked, cannot use authentication method.');
                app('session')->flash('logoutMessage', $message);
                // @noinspection PhpUndefinedMethodInspection
                $this->auth->logout(); // @phpstan-ignore-line (thinks function is undefined)

                throw new AuthenticationException('Blocked account.', $guards);
            }
        }
    }
}
