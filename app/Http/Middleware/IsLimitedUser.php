<?php
/**
 * IsLimitedUser.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Middleware;

use Closure;
use FireflyIII\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;

/**
 * Class IsAdmin
 *
 * @package FireflyIII\Http\Middleware
 */
class IsLimitedUser
{
    /**
     * Handle an incoming request. May not be a limited user (ie. Sandstorm env. or demo user).
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @param  string|null              $guard
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->guest()) {
            if ($request->ajax()) {
                return response('Unauthorized.', 401);
            }

            return redirect()->guest('login');
        }
        /** @var User $user */
        $user = auth()->user();
        if ($user->hasRole('demo')) {
            Session::flash('warning', strval(trans('firefly.not_available_demo_user')));

            return redirect(route('index'));
        }

        if (intval(getenv('SANDSTORM')) === 1) {
            Session::flash('warning', strval(trans('firefly.sandstorm_not_available')));

            return redirect(route('index'));
        }

        return $next($request);
    }
}
