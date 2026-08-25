<?php

namespace FireflyIII\Http\Middleware;

use Closure;
use FireflyIII\Exceptions\FireflyException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class CatchBlockedUsers
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     *
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next)
    {
        if(auth()->check()) {
            $user = auth()->user();
            if(null !== $user) {
                if(false !== $user->blocked || '' !== (string) $user->blocked_code) {
                    throw new AuthenticationException(sprintf('User is not allowed to use the API ("%s")', $user->blocked_code));
                }
            }
        }
        return $next($request);
    }
}
