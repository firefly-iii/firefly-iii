<?php

namespace Firefly\Helper\Toolkit;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Class Toolkit
 *
 * @package Firefly\Helper\Toolkit
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Toolkit implements ToolkitInterface
{


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|mixed|null
     */
    public function getDateRange(Request $request)
    {
        $range = $this->_getRange();
        $start = $this->_getStartDate();
        $end = $this->_getEndDate();

        // update start only:
        $start = $this->_updateStartDate($range, $start);

        // update end only:
        $end = $this->_updateEndDate($range, $start, $end);

        if (\Input::get('action') == 'prev') {
            $start = $this->_moveStartPrevious($range, $start);
            $end = $this->_moveEndPrevious($range, $end);
        }
        if (\Input::get('action') == 'next') {
            $start = $this->_moveStartNext($range, $start);
            $end = $this->_moveEndNext($range, $end);
        }

        // save in session:
        \Session::put('start', $start);
        \Session::put('end', $end);
        \Session::put('range', $range);
        if (!is_null(\Input::get('action'))) {
            return \Redirect::to($request->url());

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

    /**
     * @return mixed
     */
    protected function _getrange()
    {
        if (!is_null(\Input::get('range'))) {
            $range = \Input::get('range');
        } else {
            if (!is_null(\Session::get('range'))) {
                $range = \Session::get('range');
            } else {
                /** @noinspection PhpUndefinedClassInspection */
                $preferences = \App::make('Firefly\Helper\Preferences\PreferencesHelperInterface');
                $viewRange = $preferences->get('viewRange', '1M');

                // default range:
                $range = $viewRange->data;
            }
        }

        return $range;

    }

    /**
     * @return Carbon|mixed
     */
    protected function _getStartDate()
    {
        $start = \Session::has('start') ? \Session::get('start') : new Carbon;
        if (\Input::get('start') && \Input::get('end')) {
            $start = new Carbon(\Input::get('start'));
        }

        return $start;
    }

    /**
     * @return Carbon|mixed
     */
    protected function _getEndDate()
    {
        $end = \Session::has('end') ? \Session::get('end') : new Carbon;
        if (\Input::get('start') && \Input::get('end')) {
            $end = new Carbon(\Input::get('end'));
        }

        return $end;
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

    /**
     * @param        $range
     * @param Carbon $start
     *
     * @return Carbon
     */
    protected function _moveStartPrevious($range, Carbon $start)
    {
        switch ($range) {
            case '1D':
                $start->subDay();
                break;
            case '1W':
                $start->subWeek();
                break;
            case '1M':
                $start->subMonth();
                break;
            case '3M':
                $start->subMonths(3)->firstOfQuarter();
                break;
            case '6M':
                $start->subMonths(6);
                break;
        }
        return $start;
    }

    /**
     * @param        $range
     * @param Carbon $end
     *
     * @return Carbon
     */
    protected function _moveEndPrevious($range, Carbon $end)
    {
        switch ($range) {
            case '1D':
                $end->subDay();
                break;
            case '1W':
                $end->subWeek();
                break;
            case '1M':
                $end->startOfMonth()->subMonth()->endOfMonth();
                break;
            case '3M':
                $end->subMonths(3)->lastOfQuarter();
                break;
            case '6M':
                $end->subMonths(6);
                break;
        }
        return $end;

    }

    /**
     * @param        $range
     * @param Carbon $start
     *
     * @return Carbon
     */
    protected function _moveStartNext($range, Carbon $start)
    {
        switch ($range) {
            case '1D':
                $start->addDay();
                break;
            case '1W':
                $start->addWeek();
                break;
            case '1M':
                $start->addMonth();
                break;
            case '3M':
                $start->addMonths(3)->firstOfQuarter();
                break;
            case '6M':
                $start->addMonths(6);
                break;
        }
        return $start;
    }

    /**
     * @param        $range
     * @param Carbon $end
     *
     * @return Carbon
     */
    protected function _moveEndNext($range, Carbon $end)
    {
        switch ($range) {
            case '1D':
                $end->addDay();
                break;
            case '1W':
                $end->addWeek();
                break;
            case '1M':
                $end->addMonth();
                break;
            case '3M':
                $end->addMonths(6)->lastOfQuarter();
                break;
            case '6M':
                $end->addMonths(6);
                break;
        }
        return $end;
    }

} 