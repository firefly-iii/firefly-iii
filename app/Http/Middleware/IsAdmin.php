<?php
/**
 * IsAdmin.php
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
use FireflyIII\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class IsAdmin
 *
 * @package FireflyIII\Http\Middleware
 */
class IsAdmin
{
    /**
     * Handle an incoming request. Must be admin.
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
        if (!$user->hasRole('owner')) {
            return redirect(route('home'));
        }

        return $next($request);
    }
}
