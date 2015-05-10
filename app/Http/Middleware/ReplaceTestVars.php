<?php

namespace FireflyIII\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Log;

/**
 * Class ReplaceTestVars
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Http\Middleware
 */
class ReplaceTestVars
{
    /**
     * The application implementation.
     *
     * @var Application
     */
    protected $app;

    /**
     * Create a new filter instance.
     *
     * @param Application $app
     *
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ('testing' === $this->app->environment() && $request->has('_token')) {
            $input           = $request->all();
            $input['_token'] = $request->session()->token();
            // we need to update _token value to make sure we get the POST / PUT tests passed.
            Log::debug('Input token replaced (' . $input['_token'] . ').');
            $request->replace($input);
        }

        return $next($request);
    }

}
