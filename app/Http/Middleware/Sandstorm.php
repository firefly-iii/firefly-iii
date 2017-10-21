<?php
/**
 * Sandstorm.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Middleware;

use Auth;
use Closure;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Role;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\Request;
use View;

/**
 * Class Sandstorm
 *
 * @package FireflyIII\Http\Middleware
 */
class Sandstorm
{
    /**
     * Detects if is using Sandstorm, and responds by logging the user
     * in and/or creating an account.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @param  string|null              $guard
     *
     * @return mixed
     * @throws FireflyException
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {
        // is in Sandstorm environment?
        $sandstorm = intval(getenv('SANDSTORM')) === 1;
        View::share('SANDSTORM', $sandstorm);
        if (!$sandstorm) {
            return $next($request);
        }

        // we're in sandstorm! is user a guest?
        if (Auth::guard($guard)->guest()) {
            /** @var UserRepositoryInterface $repository */
            $repository = app(UserRepositoryInterface::class);
            $userId     = strval($request->header('X-Sandstorm-User-Id'));
            $count      = $repository->count();

            // if there already is one user in this instance, we assume this is
            // the "main" user. Firefly's nature does not allow other users to
            // access the same data so we have no choice but to simply login
            // the new user to the same account and just forget about Bob and Alice
            // and any other differences there may be between these users.
            if ($count === 1 && strlen($userId) > 0) {
                // login as first user user.
                $user = User::first();
                Auth::guard($guard)->login($user);
                View::share('SANDSTORM_ANON', false);

                return $next($request);
            }

            if ($count === 1 && strlen($userId) === 0) {
                // login but indicate anonymous
                $user = User::first();
                Auth::guard($guard)->login($user);
                View::share('SANDSTORM_ANON', true);

                return $next($request);
            }

            if ($count === 0 && strlen($userId) > 0) {
                // create new user.
                $email = $userId . '@firefly';
                /** @var User $user */
                $user = User::create(
                    [
                        'email'    => $email,
                        'password' => str_random(16),
                    ]
                );
                Auth::guard($guard)->login($user);

                // also make the user an admin
                $admin = Role::where('name', 'owner')->first();
                $user->attachRole($admin);
                $user->save();

                return $next($request);
            }

            if ($count === 0 && strlen($userId) === 0) {
                throw new FireflyException('The first visit to a new Firefly III administration cannot be by a guest user.');
            }

            if ($count > 1) {
                throw new FireflyException('Your Firefly III installation has more than one user, which is weird.');
            }

        }

        return $next($request);
    }
}
