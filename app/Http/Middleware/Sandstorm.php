<?php
/**
 * Sandstorm.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Middleware;

use Auth;
use Closure;
use FireflyIII\User;
use Illuminate\Http\Request;

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
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {
        // is in Sandstorm environment?
        $sandstorm = intval(getenv('SANDSTORM')) === 1;
        if (!$sandstorm) {
            return $next($request);
        }

        // we're in sandstorm! is user a guest?
        if (Auth::guard($guard)->guest()) {
            $userId = strval($request->header('X-Sandstorm-User-Id'));
            if (strlen($userId) > 0) {
                // find user?
                $email = $userId . '@firefly';
                $user  = User::whereEmail($email)->first();
                if (is_null($user)) {
                    $user = User::create(
                        [
                            'email'    => $email,
                            'password' => str_random(16),
                        ]
                    );
                }


                // login user:
                Auth::guard($guard)->login($user);
            } else {
                echo 'user id no length, guest?';
                exit;
            }

        }

        return $next($request);
    }
}
