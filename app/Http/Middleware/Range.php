<?php
/**
 * Range.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Middleware;

use Amount;
use App;
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
     * @param Closure                   $next
     * @param  string|null              $guard
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {
        if (!Auth::guard($guard)->guest()) {

            // set start, end and finish:
            $this->setRange();

            // set view variables.
            $this->configureView();

            // set more view variables:
            $this->configureList();
        }

        return $next($request);

    }

    /**
     *
     */
    private function configureList()
    {
        $pref = Preferences::get('list-length', config('firefly.list_length', 10))->data;
        View::share('listLength', $pref);
    }

    private function configureView()
    {
        $pref = Preferences::get('language', config('firefly.default_language', 'en_US'));
        $lang = $pref->data;
        App::setLocale($lang);
        Carbon::setLocale(substr($lang, 0, 2));
        $locale = explode(',', trans('config.locale'));
        $locale = array_map('trim', $locale);

        setlocale(LC_TIME, $locale);
        $moneyResult = setlocale(LC_MONETARY, $locale);

        // send error to view if could not set money format
        if($moneyResult === false) {
            View::share('invalidMonetaryLocale', true);
        }



        // save some formats:
        $monthAndDayFormat = (string)trans('config.month_and_day');
        $dateTimeFormat    = (string)trans('config.date_time');
        $defaultCurrency   = Amount::getDefaultCurrency();

        View::share('monthAndDayFormat', $monthAndDayFormat);
        View::share('dateTimeFormat', $dateTimeFormat);
        View::share('defaultCurrency', $defaultCurrency);
    }

    /**
     *
     */
    private function setRange()
    {
        // ignore preference. set the range to be the current month:
        if (!Session::has('start') && !Session::has('end')) {

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
    }

}
