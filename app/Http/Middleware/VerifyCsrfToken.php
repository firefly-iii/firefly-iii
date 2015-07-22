<?php namespace FireflyIII\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

/**
 * Class VerifyCsrfToken
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Http\Middleware
 */
class VerifyCsrfToken extends BaseVerifier
{

    /**
     * Routes we want to exclude from CSRF.
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->excludedRoutes($request)) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    /**
     * This will return a bool value based on route checking.
     *
     * @param  Request $request
     *
     * @return boolean
     */
    protected function excludedRoutes($request)
    {
        foreach ($this->routes as $route) {
            if ($request->is($route)) {
                return true;
            }
        }

        return false;
    }

}
