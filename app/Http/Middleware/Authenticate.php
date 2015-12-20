<?php namespace FireflyIII\Http\Middleware;

use App;
use Auth;
use Carbon\Carbon;
use Closure;
use Config;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Preferences;

/**
 * Class Authenticate
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Http\Middleware
 */
class Authenticate
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
        if ($this->auth->guest()) {
            if ($request->ajax()) {
                return response('Unauthorized.', 401);
            } else {
                return redirect()->guest('auth/login');
            }
        }

        if (intval($this->auth->user()->blocked) == 1) {
            Auth::logout();

            return redirect()->route('index');
        }

        // if logged in, set user language:
        $pref = Preferences::get('language', env('DEFAULT_LANGUAGE','en_US'));
        App::setLocale($pref->data);
        Carbon::setLocale(substr($pref->data,0,2));

        setlocale(LC_TIME, Config::get('firefly.locales.' . $pref->data));
        setlocale(LC_MONETARY, Config::get('firefly.locales.' . $pref->data));

        return $next($request);
    }

}
