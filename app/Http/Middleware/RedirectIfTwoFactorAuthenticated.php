<?php
/**
 * RedirectIfTwoFactorAuthenticated.php
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
use Illuminate\Support\Facades\Auth;
use Preferences;

/**
 * Class RedirectIfTwoFactorAuthenticated.
 */
class RedirectIfTwoFactorAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string|null              $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {
            $is2faEnabled = Preferences::get('twoFactorAuthEnabled', false)->data;
            $has2faSecret = null !== Preferences::get('twoFactorAuthSecret');

            // grab 2auth information from session.
            $is2faAuthed = true === $request->session()->get('twoFactorAuthenticated');

            if ($is2faEnabled && $has2faSecret && $is2faAuthed) {
                return redirect('/');
            }
        }

        return $next($request);
    }
}
