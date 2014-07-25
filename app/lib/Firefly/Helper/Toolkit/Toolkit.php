<?php

namespace Firefly\Helper\Toolkit;

use Carbon\Carbon;

/**
 * Class Toolkit
 *
 * @package Firefly\Helper\Toolkit
 */
class Toolkit implements ToolkitInterface
{

    /**
     * Based on the preference 'viewRange' and other variables I have not yet thought of,
     * this method will return a date range that defines the 'current' period of time.
     *
     * ie. the current week or month.
     *
     * $start is always the past, $end is 'now' or at least later.
     */
    public function getDateRange()
    {
        $preferences = \App::make('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $viewRange = $preferences->get('viewRange', '1M');

        // default range:
        $range = $viewRange->data;

        // update range if session has something:
        if (!is_null(\Session::get('range'))) {
            $range = \Session::get('range');
        }

        // update view range if the input has something:
        if (!is_null(\Input::get('range'))) {
            $range = \Input::get('range');
        }

        // switch $range, update range or something:
        $start = \Session::has('start') ? \Session::get('start') : new Carbon;
        $end = \Session::has('end') ? \Session::get('end') : new Carbon;
        $today = new Carbon;
        \Log::debug('Start: ' . $start . ' (' . \Session::has('start') . ')');
        \Log::debug('End: ' . $end);

        // see if we have to do a prev / next thing:
        $doPrev = false;
        $doNext = false;
        if (\Input::get('action') == 'prev') {
            $doPrev = true;
        }
        if (\Input::get('action') == 'next') {
            $doNext = true;
        }


        switch ($range) {
            case 'custom':
                // when range is custom AND input, we ignore $today
                if (\Input::get('start') && \Input::get('end')) {
                    $start = new Carbon(\Input::get('start'));
                    $end = new Carbon(\Input::get('end'));
                } else {
                    $start = \Session::get('start');
                    $end = \Session::get('end');
                }
                break;
            case '1D':
                $start->startOfDay();
                $end = clone $start;
                $end->endOfDay();
                if ($doNext) {
                    $start->addDay();
                    $end->addDay();
                }
                if ($doPrev) {
                    $start->subDay();
                    $end->subDay();
                }
                break;
            case '1W':
                $start->startOfWeek();
                $end = clone $start;
                $end->endOfWeek();
                if ($doNext) {
                    $start->addWeek();
                    $end->addWeek();
                }
                if ($doPrev) {
                    $start->subWeek();
                    $end->subWeek();
                }
                break;
            case '1M':
                $start->startOfMonth();
                $end = clone $start;
                $end->endOfMonth();
                if ($doNext) {
                    $start->addMonth();
                    $end->addMonth();
                }
                if ($doPrev) {
                    $start->subMonth();
                    \Log::debug('1M prev. Before: ' . $end);
                    $end->startOfMonth()->subMonth()->endOfMonth();
                    \Log::debug('1M prev. After: ' . $end);
                }
                break;
            case '3M':
                $start->firstOfQuarter();
                $end = clone $start;
                $end->lastOfQuarter();
                if ($doNext) {
                    $start->addMonths(3)->firstOfQuarter();
                    $end->addMonths(6)->lastOfQuarter();
                }
                if ($doPrev) {
                    $start->subMonths(3)->firstOfQuarter();
                    $end->subMonths(3)->lastOfQuarter();
                }
                break;
            case '6M':
                if (intval($today->format('m')) >= 7) {
                    $start->startOfYear()->addMonths(6);
                    $end = clone $start;
                    $end->endOfYear();
                } else {
                    $start->startOfYear();
                    $end = clone $start;
                    $end->startOfYear()->addMonths(6);
                }
                if ($doNext) {
                    $start->addMonths(6);
                    $end->addMonths(6);
                }
                if ($doPrev) {
                    $start->subMonths(6);
                    $end->subMonths(6);
                }
                break;
        }
        // save in session:
        \Session::put('start', $start);
        \Session::put('end', $end);
        \Session::put('range', $range);
        if ($doPrev || $doNext) {
            return \Redirect::route('index');

        }
        return null;


    }

    /**
     * @return array
     */
    public function getDateRangeDates()
    {
        return [\Session::get('start'), \Session::get('end')];
    }

} 