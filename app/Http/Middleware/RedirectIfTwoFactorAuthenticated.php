<?php
/**
 * RedirectIfTwoFactorAuthenticated.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Preferences;
use Session;

/**
 * Class RedirectIfTwoFactorAuthenticated
 *
 * @package FireflyIII\Http\Middleware
 */
class RedirectIfTwoFactorAuthenticated
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
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {

            $twoFactorAuthEnabled     = Preferences::get('twoFactorAuthEnabled', false)->data;
            $hasTwoFactorAuthSecret   = !is_null(Preferences::get('twoFactorAuthSecret'));
            $isTwoFactorAuthenticated = Session::get('twofactor-authenticated');
            if ($twoFactorAuthEnabled && $hasTwoFactorAuthSecret && $isTwoFactorAuthenticated) {
                return redirect('/');
            }
        }

        return $next($request);
    }
}
