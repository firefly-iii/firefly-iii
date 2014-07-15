<?php

namespace Firefly\Helper\Toolkit;


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
    public static function getDateRange()
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
        $today = new \Carbon\Carbon;
        $start = clone $today;
        $end = clone $today;
        switch ($range) {
            case 'custom':
                // when range is custom AND input, we ignore $today
                if (\Input::get('start') && \Input::get('end')) {
                    $start = new \Carbon\Carbon(\Input::get('start'));
                    $end = new \Carbon\Carbon(\Input::get('end'));
                } else {
                    $start = \Session::get('start');
                    $end = \Session::get('end');
                }
                break;
            case '1D':
                $start->startOfDay();
                $end->endOfDay();
                break;
            case '1W':
                $start->startOfWeek();
                $end->endOfWeek();
                break;
            case '1M':
                $start->startOfMonth();
                $end->endOfMonth();
                break;
            case '3M':
                $start->firstOfQuarter();
                $end->lastOfQuarter();
                break;
            case '6M':
                if (intval($today->format('m')) >= 7) {
                    $start->startOfYear()->addMonths(6);
                    $end->endOfYear();
                } else {
                    $start->startOfYear();
                    $end->startOfYear()->addMonths(6);
                }
                break;
        }
        // save in session:
        \Session::put('start', $start);
        \Session::put('end', $end);
        \Session::put('range', $range);

        // and return:
        return [$start, $end];


    }

} 