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
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return parent::handle($request, $next);
    }

}
