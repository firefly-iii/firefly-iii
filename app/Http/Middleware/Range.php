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

declare(strict_types = 1);

namespace FireflyIII\Http\Middleware;

use App;
use Carbon\Carbon;
use Closure;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Navigation;
use NumberFormatter;
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

            // set start, end and finish:
            $this->setRange();

            // get variables for date range:
            $this->datePicker();

            // set view variables.
            $this->configureView();
        }

        return $theNext($request);

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
        setlocale(LC_MONETARY, $locale);

        // save some formats:
        $monthFormat       = (string)trans('config.month');
        $monthAndDayFormat = (string)trans('config.month_and_day');
        $dateTimeFormat    = (string)trans('config.date_time');

        // change localeconv to a new array:
        $numberFormatter = numfmt_create($lang, NumberFormatter::CURRENCY);
        $localeconv      = [
            'mon_decimal_point' => $numberFormatter->getSymbol($numberFormatter->getAttribute(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL)),
            'mon_thousands_sep' => $numberFormatter->getSymbol($numberFormatter->getAttribute(NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL)),
            'frac_digits'       => $numberFormatter->getAttribute(NumberFormatter::MAX_FRACTION_DIGITS),
        ];
        View::share('monthFormat', $monthFormat);
        View::share('monthAndDayFormat', $monthAndDayFormat);
        View::share('dateTimeFormat', $dateTimeFormat);
        View::share('language', $lang);
        View::share('localeconv', $localeconv);
    }

    /**
     * @throws FireflyException
     */
    private function datePicker()
    {
        $viewRange          = Preferences::get('viewRange', '1M')->data;
        $start              = Session::get('start');
        $end                = Session::get('end');
        $prevStart          = Navigation::subtractPeriod($start, $viewRange);// subtract for previous period
        $prevEnd            = Navigation::endOfPeriod($prevStart, $viewRange);
        $nextStart          = Navigation::addPeriod($start, $viewRange, 0); // add for previous period
        $nextEnd            = Navigation::endOfPeriod($nextStart, $viewRange);
        $ranges             = [];
        $ranges['current']  = [$start->format('Y-m-d'), $end->format('Y-m-d')];
        $ranges['previous'] = [$prevStart->format('Y-m-d'), $prevEnd->format('Y-m-d')];
        $ranges['next']     = [$nextStart->format('Y-m-d'), $nextEnd->format('Y-m-d')];

        switch ($viewRange) {
            default:
                throw new FireflyException('The date picker does not yet support "' . $viewRange . '".');
            case '1D':
                $format = (string)trans('config.month_and_day');
                break;
            case '3M':
                $format = (string)trans('config.quarter_in_year');
                break;
            case '6M':
                $format = (string)trans('config.half_year');
                break;
            case '1Y':
                $format = (string)trans('config.year');
                break;
            case '1M':
                $format = (string)trans('config.month');
                break;
            case '1W':
                $format = (string)trans('config.week_in_year');
                break;
        }


        $current = $start->formatLocalized($format);
        $next    = $nextStart->formatLocalized($format);
        $prev    = $prevStart->formatLocalized($format);
        View::share('dpStart', $start->format('Y-m-d'));
        View::share('dpEnd', $end->format('Y-m-d'));
        View::share('dpCurrent', $current);
        View::share('dpPrevious', $prev);
        View::share('dpNext', $next);
        View::share('dpRanges', $ranges);
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
