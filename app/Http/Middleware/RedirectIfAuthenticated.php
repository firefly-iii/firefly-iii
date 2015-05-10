<?php namespace FireflyIII\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Class RedirectIfAuthenticated
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Http\Middleware
 */
class RedirectIfAuthenticated
{

    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param  Guard $auth
     *
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->auth->check()) {
            return new RedirectResponse(url('/'));
        }

        return $next($request);
    }

}
