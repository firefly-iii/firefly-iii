<?php

namespace Firefly\Helper\Toolkit;

use Carbon\Carbon;

/**
 * Class Toolkit
 *
 * @package Firefly\Helper\Toolkit
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class Toolkit implements ToolkitInterface
{


    /**
     * Lots of code in Firefly III still depends on session['start'], session['end'] and
     * session['range'] to be available, even though this feature has been removed from Firefly
     * in favor of a new reporting feature. This reporting feature can show the user past and future
     * date ranges instead of the dashboard (the dashboard always shows "right now").
     *
     * The only actual choice the user is left with is the range, which can be changed using the Preferences pane.
     *
     * The start/end dates are set here, regardless of what the user might want to see.
     *
     * @return null
     */
    public function getDateRange()
    {
        $range = $this->_getRange();
        // start and end are always "now", and get edited later.
        $start = new Carbon;
        $end   = new Carbon;

        // update start only:
        $start = $this->_updateStartDate($range, $start);

        // update end only:
        $end = $this->_updateEndDate($range, $start, $end);

        // save in session:
        \Session::put('start', $start);
        \Session::put('end', $end);
        \Session::put('range', $range);
        return null;

    }

    /**
     * @return mixed
     */
    protected function _getRange()
    {
        if (!is_null(\Session::get('range'))) {
            $range = \Session::get('range');
        } else {
            /** @noinspection PhpUndefinedClassInspection */
            $preferences = \App::make('Firefly\Helper\Preferences\PreferencesHelperInterface');
            $viewRange   = $preferences->get('viewRange', '1M');

            // default range:
            $range = $viewRange->data;
        }
        return $range;

    }

    /**
     * @param        $range
     * @param Carbon $start
     *
     * @return Carbon
     */
    protected function _updateStartDate($range, Carbon $start)
    {
        $today = new Carbon;
        switch ($range) {
            case '1D':
                $start->startOfDay();
                break;
            case '1W':
                $start->startOfWeek();
                break;
            case '1M':
                $start->startOfMonth();
                break;
            case '3M':
                $start->firstOfQuarter();
                break;
            case '6M':
                if (intval($today->format('m')) >= 7) {
                    $start->startOfYear()->addMonths(6);
                } else {
                    $start->startOfYear();
                }
                break;
        }

        return $start;

    }

    /**
     * @param        $range
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Carbon
     */
    protected function _updateEndDate($range, Carbon $start, Carbon $end)
    {
        $today = new Carbon;
        switch ($range) {
            case '1D':
                $end = clone $start;
                $end->endOfDay();
                break;
            case '1W':
                $end = clone $start;
                $end->endOfWeek();
                break;
            case '1M':
                $end = clone $start;
                $end->endOfMonth();
                break;
            case '3M':
                $end = clone $start;
                $end->lastOfQuarter();
                break;
            case '6M':
                $end = clone $start;
                if (intval($today->format('m')) >= 7) {
                    $end->endOfYear();
                } else {
                    $end->startOfYear()->addMonths(6);
                }
                break;
        }

        return $end;
    }
}