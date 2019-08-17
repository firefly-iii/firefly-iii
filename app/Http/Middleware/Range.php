<?php
/**
 * Range.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Middleware;

use App;
use Carbon\Carbon;
use Closure;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Http\Request;

/**
 * Class SessionFilter.
 */
class Range
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure                  $next
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

            // flash a big fat warning when users use SQLite in Docker
            $this->loseItAll($request);
        }

        return $next($request);
    }

    /**
     * Configure the list length.
     */
    private function configureList(): void
    {
        $pref = app('preferences')->get('list-length', config('firefly.list_length', 10))->data;
        app('view')->share('listLength', $pref);
    }

    /**
     * Configure the user's view.
     */
    private function configureView(): void
    {
        $pref = app('preferences')->get('language', config('firefly.default_language', 'en_US'));
        /** @noinspection NullPointerExceptionInspection */
        $lang = $pref->data;
        App::setLocale($lang);
        Carbon::setLocale(substr($lang, 0, 2));
        $locale = explode(',', (string)trans('config.locale'));
        $locale = array_map('trim', $locale);

        setlocale(LC_TIME, $locale);
        $moneyResult = setlocale(LC_MONETARY, $locale);

        // send error to view if could not set money format
        if (false === $moneyResult) {
            app('view')->share('invalidMonetaryLocale', true); // @codeCoverageIgnore
        }

        // save some formats:
        $monthAndDayFormat = (string)trans('config.month_and_day');
        $dateTimeFormat    = (string)trans('config.date_time');
        $defaultCurrency   = app('amount')->getDefaultCurrency();

        app('view')->share('monthAndDayFormat', $monthAndDayFormat);
        app('view')->share('dateTimeFormat', $dateTimeFormat);
        app('view')->share('defaultCurrency', $defaultCurrency);
    }

    /**
     * Error when sqlite in docker.
     *
     * @param Request $request
     */
    private function loseItAll(Request $request): void
    {
        if ('sqlite' === config('database.default') && true === config('firefly.is_docker')) {
            // @codeCoverageIgnoreStart
            $request->session()->flash(
                'error', 'You seem to be using SQLite in a Docker container. Don\'t do this. If the container restarts all your data will be gone.'
            );
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Set the range for the current view.
     */
    private function setRange(): void
    {
        // ignore preference. set the range to be the current month:
        if (!app('session')->has('start') && !app('session')->has('end')) {
            $viewRange = app('preferences')->get('viewRange', '1M')->data;
            $start     = app('navigation')->updateStartDate($viewRange, new Carbon);
            $end       = app('navigation')->updateEndDate($viewRange, $start);

            app('session')->put('start', $start);
            app('session')->put('end', $end);
        }
        if (!app('session')->has('first')) {
            /** @var JournalRepositoryInterface $repository */
            $repository = app(JournalRepositoryInterface::class);
            $journal    = $repository->firstNull();
            $first      = Carbon::now()->startOfYear();

            if (null !== $journal) {
                $first = $journal->date ?? $first;
            }
            app('session')->put('first', $first);
        }
    }
}
