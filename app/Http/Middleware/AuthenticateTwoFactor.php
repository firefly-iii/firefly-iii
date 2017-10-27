<?php
/**
 * AuthenticateTwoFactor.php
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

use Closure;
use Cookie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Log;
use Preferences;
use Session;

/**
 * Class AuthenticateTwoFactor
 *
 * @package FireflyIII\Http\Middleware
 */
class AuthenticateTwoFactor
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

        // do the usual auth, again:
        if (Auth::guard($guard)->guest()) {
            if ($request->ajax()) {
                return response('Unauthorized.', 401);
            }

            return redirect()->guest('login');
        }

        if (intval(auth()->user()->blocked) === 1) {
            Auth::guard($guard)->logout();
            Session::flash('logoutMessage', trans('firefly.block_account_logout'));

            return redirect()->guest('login');
        }
        $is2faEnabled = Preferences::get('twoFactorAuthEnabled', false)->data;
        $has2faSecret = !is_null(Preferences::get('twoFactorAuthSecret'));

        // grab 2auth information from cookie, not from session.
        $is2faAuthed = Cookie::get('twoFactorAuthenticated') === 'true';

        if ($is2faEnabled && $has2faSecret && !$is2faAuthed) {
            Log::debug('Does not seem to be 2 factor authed, redirect.');

            return redirect(route('two-factor.index'));
        }

        return $next($request);
    }
}
