<?php

namespace FireflyIII\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Session;

/**
 * Class Authenticate
 *
 * @package FireflyIII\Http\Middleware
 */
class TwoFactorAuthenticate
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
        $is2faEnabled = Auth::user()->is2faEnabled();

        if($is2faEnabled)
        {            
            if(!Session::has('auth.2fa_passed'))
            {
                return redirect()->guest('verify_token');
            }

        }
        
        return $next($request);     
    }
}
