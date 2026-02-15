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

use Closure;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Exceptions\Handler;
use FireflyIII\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Server\Exception\OAuthServerException;

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
    ) {}

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
    public function handle($request, Closure $next, ...$guards)
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
            Log::debug('in Authenticate::authenticate() with zero guards.');
            // There are no guards defined, go for the default guard:
            if (auth()->check()) {
                Log::debug('User is authenticated.');
                $user = auth()->user();
                $this->validateBlockedUser($user, $guards);

                return;
            }
            // @noinspection PhpUndefinedMethodInspection
            $this->auth->authenticate();
            if (!$this->auth->check()) {
                throw new AuthenticationException('The user is not logged in but must be.', $guards);
            }
        }

        exit('five');
        foreach ($guards as $guard) {
            exit('six');
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

        exit('seven');
        // this is a massive hack, but if the handler has the oauth exception
        // at this point we can report its error instead of a generic one.
        $message = 'Unauthenticated.';
        if (Handler::$lastError instanceof OAuthServerException) {
            $message = Handler::$lastError->getHint();
        }

        throw new AuthenticationException($message, $guards);
    }

    /**
     * @throws AuthenticationException
     */
    private function validateBlockedUser(?User $user, array $guards): void
    {
        if (!$user instanceof User) {
            Log::warning('User is null, throw exception?');
        }
        // \Illuminate\Support\Facades\Log::debug(get_class($user));
        if ($user instanceof User && 1 === (int) $user->blocked) {
            $message = (string) trans('firefly.block_account_logout');
            if ('email_changed' === $user->blocked_code) {
                $message = (string) trans('firefly.email_changed_logout');
            }
            Log::warning('User is blocked, cannot use authentication method.');
            app('session')->flash('logoutMessage', $message);
            // @noinspection PhpUndefinedMethodInspection
            $this->auth->logout();

            // @phpstan-ignore-line (thinks function is undefined)
            throw new AuthenticationException('Blocked account.', $guards);
        }
        Log::debug(sprintf('User #%d is not blocked.', $user->id));
    }
}
