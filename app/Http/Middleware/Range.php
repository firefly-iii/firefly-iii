<?php
/**
 * Range.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Middleware;

use App;
use Carbon\Carbon;
use Closure;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\RequestInformation;
use Illuminate\Http\Request;
use Log;

/**
 * Class SessionFilter.
 */
class Range
{
    use RequestInformation;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
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
     * Set the range for the current view.
     */
    private function setRange(): void
    {
        // ignore preference. set the range to be the current month:
        if (!app('session')->has('start') && !app('session')->has('end')) {
            $viewRange = app('preferences')->get('viewRange', '1M')->data;
            $today     = today(config('app.timezone'));
            $start     = app('navigation')->updateStartDate($viewRange, $today);
            $end       = app('navigation')->updateEndDate($viewRange, $start);

            app('session')->put('start', $start);
            app('session')->put('end', $end);
        }
        if (!app('session')->has('first')) {
            /** @var JournalRepositoryInterface $repository */
            $repository = app(JournalRepositoryInterface::class);
            $journal    = $repository->firstNull();
            $first      = today(config('app.timezone'))->startOfYear();

            if (null !== $journal) {
                $first = $journal->date ?? $first;
            }
            app('session')->put('first', $first);
        }
    }

    /**
     * Configure the user's view.
     */
    private function configureView(): void
    {
        // get locale preference:
        $language = app('steam')->getLanguage();
        $locale   = app('steam')->getLocale();
        App::setLocale($language);
        Carbon::setLocale(substr($locale, 0, 2));

        $localeArray = app('steam')->getLocaleArray($locale);

        setlocale(LC_TIME, $localeArray);
        $moneyResult = setlocale(LC_MONETARY, $localeArray);

        // send error to view if could not set money format
        if (false === $moneyResult) {
            Log::error('Could not set locale. The following array doesnt work: ', $localeArray);
            app('view')->share('invalidMonetaryLocale', true); 
        }

        // save some formats:
        $monthAndDayFormat = (string)trans('config.month_and_day', [], $locale);
        $dateTimeFormat    = (string)trans('config.date_time', [], $locale);
        $defaultCurrency   = app('amount')->getDefaultCurrency();

        // also format for moment JS:
        $madMomentJS = (string)trans('config.month_and_day_moment_js', [], $locale);

        app('view')->share('madMomentJS', $madMomentJS);
        app('view')->share('monthAndDayFormat', $monthAndDayFormat);
        app('view')->share('dateTimeFormat', $dateTimeFormat);
        app('view')->share('defaultCurrency', $defaultCurrency);
    }

    /**
     * Configure the list length.
     */
    private function configureList(): void
    {
        $pref = app('preferences')->get('list-length', config('firefly.list_length', 10))->data;
        app('view')->share('listLength', $pref);
    }
}
