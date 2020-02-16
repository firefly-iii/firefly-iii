<?php
/**
 * Sandstorm.php
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
/** @noinspection PhpDynamicAsStaticMethodCallInspection */
declare(strict_types=1);

namespace FireflyIII\Http\Middleware;

use Auth;
use Closure;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\Request;
use Log;

/**
 * Class Sandstorm.
 */
class Sandstorm
{
    /**
     * Detects if is using Sandstorm, and responds by logging the user
     * in and/or creating an account.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string|null              $guard
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {
        // is in Sandstorm environment?
        $sandstorm = 1 === (int)getenv('SANDSTORM');
        app('view')->share('SANDSTORM', $sandstorm);
        if (!$sandstorm) {
            return $next($request);
        }

        // we're in sandstorm! is user a guest?
        if (Auth::guard($guard)->guest()) {
            /** @var UserRepositoryInterface $repository */
            $repository = app(UserRepositoryInterface::class);
            $userId     = (string)$request->header('X-Sandstorm-User-Id');

            // catch anonymous:
            $userId = '' === $userId ? 'anonymous' : $userId;
            $email  = $userId . '@firefly';

            // always grab the first user in the Sandstorm DB:
            $user = $repository->findByEmail($email) ?? $repository->first();
            // or create somebody if necessary.
            $user = $user ?? $this->createUser($email);

            // then log this user in:
            Log::info(sprintf('Sandstorm user ID is "%s"', $userId));
            Log::info(sprintf('Access to database under "%s"', $email));
            Auth::guard($guard)->login($user);
            $repository->attachRole($user, 'owner');
            app('view')->share('SANDSTORM_ANON', false);
        }

        return $next($request);
    }


    /**
     * Create a user.
     *
     * @param string $email
     *
     * @return User
     * @codeCoverageIgnore
     */
    private function createUser(string $email): User
    {
        $repository = app(UserRepositoryInterface::class);

        return $repository->store(
            [
                'blocked'      => false,
                'blocked_code' => null,
                'email'        => $email,
            ]
        );

    }
}


