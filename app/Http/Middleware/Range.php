<?php


namespace FireflyIII\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Auth\Guard;
use Navigation;
use Preferences;
use Session;

/**
 * Class SessionFilter
 *
 * @package FireflyIII\Http\Middleware
 */
class Range
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
     * @param  \Closure                 $theNext
     *
     * @return mixed
     */
    public function handle($request, Closure $theNext)
    {
        if ($this->auth->check()) {
            // user's view range comes from preferences, gets set in session:
            /** @var \FireflyIII\Models\Preference $viewRange */
            $viewRange = Preferences::get('viewRange', '1M');


            // the start and end date are checked and stored:
            $start  = Session::has('start') ? Session::get('start') : new Carbon;
            $start  = Navigation::updateStartDate($viewRange->data, $start);
            $end    = Navigation::updateEndDate($viewRange->data, $start);
            $period = Navigation::periodName($viewRange->data, $start);
            $prev   = Navigation::jumpToPrevious($viewRange->data, clone $start);
            $next   = Navigation::jumpToNext($viewRange->data, clone $start);

            Session::put('range', $viewRange->data);
            Session::put('start', $start);
            Session::put('end', $end);
            Session::put('period', $period);
            Session::put('prev', Navigation::periodName($viewRange->data, $prev));
            Session::put('next', Navigation::periodName($viewRange->data, $next));

        }

        return $theNext($request);

    }

}