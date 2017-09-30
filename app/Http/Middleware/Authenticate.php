<?php
/**
 * Authenticate.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;

/**
 * Class Authenticate
 *
 * @package FireflyIII\Http\Middleware
 */
class Authenticate
{
    /**
     * Handle an incoming request.
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
        if (intval(auth()->user()->blocked) === 1) {
            $message = strval(trans('firefly.block_account_logout'));
            if (auth()->user()->blocked_code === 'email_changed') {
                $message = strval(trans('firefly.email_changed_logout'));
            }

            Session::flash('logoutMessage', $message);
            Auth::guard($guard)->logout();

            return redirect()->guest('login');
        }

        return $next($request);
    }
}
