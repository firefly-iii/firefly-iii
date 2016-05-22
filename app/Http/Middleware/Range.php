<?php
/**
 * Range.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Middleware;

use Carbon\Carbon;
use Closure;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * @param Closure                   $theNext
     * @param  string|null              $guard
     *
     * @return mixed
     * @internal param Closure $next
     */
    public function handle(Request $request, Closure $theNext, $guard = null)
    {
        if (!Auth::guard($guard)->guest()) {
            // ignore preference. set the range to be the current month:
            if (!Session::has('start') && !Session::has('end')) {

                /** @var \FireflyIII\Models\Preference $viewRange */
                $viewRange = Preferences::get('viewRange', '1M')->data;
                $start     = new Carbon;
                $start     = Navigation::updateStartDate($viewRange, $start);
                $end       = Navigation::updateEndDate($viewRange, $start);

                Session::put('start', $start);
                Session::put('end', $end);
            }
            if (!Session::has('first')) {
                /** @var JournalRepositoryInterface $repository */
                $repository = app(JournalRepositoryInterface::class);
                $journal    = $repository->first();
                $first      = Carbon::now()->startOfYear();

                if (!is_null($journal->id)) {
                    $first = $journal->date;
                }
                Session::put('first', $first);
            }
            $current = Carbon::now()->formatLocalized('%B %Y');
            $next    = Carbon::now()->endOfMonth()->addDay()->formatLocalized('%B %Y');
            $prev    = Carbon::now()->startOfMonth()->subDay()->formatLocalized('%B %Y');
            View::share('currentMonthName', $current);
            View::share('previousMonthName', $prev);
            View::share('nextMonthName', $next);
        }

        return $theNext($request);

    }

}
