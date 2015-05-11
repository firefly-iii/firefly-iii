<?php


namespace FireflyIII\Http\Middleware;

use App;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Navigation;
use Preferences;
use Session;
use View;

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
    public function handle(Request $request, Closure $theNext)
    {
        if ($this->auth->check()) {

            // ignore preference. set the range to be the current month:
            if (!Session::has('start') && !Session::has('end')) {

                /** @var \FireflyIII\Models\Preference $viewRange */
                $viewRange = Preferences::get('viewRange', '1M');
                $start     = new Carbon;
                $start     = Navigation::updateStartDate($viewRange->data, $start);
                $end       = Navigation::updateEndDate($viewRange->data, $start);

                Session::put('start', $start);
                Session::put('end', $end);
            }
            if (!Session::has('first')) {
                /**
                 * Get helper thing.
                 */
                /** @var \FireflyIII\Repositories\Journal\JournalRepositoryInterface $repository */
                $repository = App::make('FireflyIII\Repositories\Journal\JournalRepositoryInterface');
                $journal    = $repository->first();
                if ($journal) {
                    Session::put('first', $journal->date);
                } else {
                    Session::put('first', Carbon::now()->startOfYear());
                }
            }

            // set current / next / prev month.
            $current = Carbon::now()->format('F Y');
            $next    = Carbon::now()->endOfMonth()->addDay()->format('F Y');
            $prev    = Carbon::now()->startOfMonth()->subDay()->format('F Y');
            View::share('currentMonthName', $current);
            View::share('previousMonthName', $prev);
            View::share('nextMonthName', $next);


        }

        return $theNext($request);

    }

}
