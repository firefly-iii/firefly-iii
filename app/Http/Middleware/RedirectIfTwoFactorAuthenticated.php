<?php
/**
 * RedirectIfTwoFactorAuthenticated.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
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

            $is2faEnabled = Preferences::get('twoFactorAuthEnabled', false)->data;
            $has2faSecret = !is_null(Preferences::get('twoFactorAuthSecret'));
            $is2faAuthed  = Session::get('twofactor-authenticated');
            if ($is2faEnabled && $has2faSecret && $is2faAuthed) {
                return redirect('/');
            }
        }

        return $next($request);
    }
}
