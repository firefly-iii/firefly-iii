<?php

namespace FireflyIII\Http\Middleware;

use App;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Auth;
use Preferences;

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
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->guest()) {
            if ($request->ajax()) {
                return response('Unauthorized.', 401);
            } else {

                return redirect()->guest('login');
            }
        }

        return $next($request);
    }
}
