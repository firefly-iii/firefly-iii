<?php

namespace FireflyIII\Shared\Toolkit;

use Carbon\Carbon;
use FireflyIII\Exception\FireflyException;

/**
 * Class Filter
 *
 * @package FireflyIII\Shared\Toolkit
 */
class Filter
{
    /**
     * Save Session::get('start') and Session::get('end') for other methods to use.
     * @CodeCoverageIgnore
     */
    public function setSessionDateRange()
    {
        $range  = $this->setSessionRangeValue();
        $start  = \Session::has('start') ? \Session::get('start') : new Carbon;
        $start  = $this->updateStartDate($range, $start);
        $end    = $this->updateEndDate($range, $start);
        $period = $this->periodName($range, $start);
        $prev   = $this->previous($range, clone $start);
        $next   = $this->next($range, clone $start);

        \Session::put('start', $start);
        \Session::put('end', $end);
        \Session::put('range', $range);
        \Session::put('period', $period);
        \Session::put('prev', $this->periodName($range, $prev));
        \Session::put('next', $this->periodName($range, $next));

        return null;

    }

    /**
     * Checks and sets the currently set 'range' or defaults to a session
     * and if that fails, defaults to 1M. Always returns the final value.
     *
     * @return string
     */
    public function setSessionRangeValue()
    {
        if (!is_null(\Session::get('range'))) {
            // @CodeCoverageIgnoreStart
            $range = \Session::get('range');
            // @CodeCoverageIgnoreEnd
        } else {
            /** @var \FireflyIII\Shared\Preferences\PreferencesInterface $preferences */
            $preferences = \App::make('FireflyIII\Shared\Preferences\PreferencesInterface');
            $viewRange   = $preferences->get('viewRange', '1M');

            // default range:
            $range = $viewRange->data;
            \Session::put('range', $range);
        }

        return $range;

    }

    /**
     * @param        $range
     * @param Carbon $start
     *
     * @return Carbon
     * @throws FireflyException
     * @CodeCoverageIgnore
     */
    protected function updateStartDate($range, Carbon $start)
    {
        $functionMap = [
            '1D' => 'startOfDay',
            '1W' => 'startOfWeek',
            '1M' => 'startOfMonth',
            '3M' => 'firstOfQuarter',
            '1Y' => 'startOfYear',
        ];
        if (isset($functionMap[$range])) {
            $function = $functionMap[$range];
            $start->$function();

            return $start;
        }
        if ($range == '6M') {
            if (intval($start->format('m')) >= 7) {
                $start->startOfYear()->addMonths(6);
            } else {
                $start->startOfYear();
            }

            return $start;
        }
        throw new FireflyException('updateStartDate cannot handle $range ' . $range);
    }

    /**
     * @param        $range
     * @param Carbon $start
     *
     * @return Carbon
     * @throws FireflyException
     * @CodeCoverageIgnore
     */
    protected function updateEndDate($range, Carbon $start)
    {
        $functionMap = [
            '1D' => 'endOfDay',
            '1W' => 'endOfWeek',
            '1M' => 'endOfMonth',
            '3M' => 'lastOfQuarter',
            '1Y' => 'endOfYear',
        ];
        $end         = clone $start;

        if (isset($functionMap[$range])) {
            $function = $functionMap[$range];
            $end->$function();

            return $end;
        }
        if ($range == '6M') {
            if (intval($start->format('m')) >= 7) {
                $end->endOfYear();
            } else {
                $end->startOfYear()->addMonths(6);
            }

            return $end;
        }
        throw new FireflyException('updateEndDate cannot handle $range ' . $range);
    }

    /**
     * @SuppressWarnings("CyclomaticComplexity") // It's exactly 5. So I don't mind.
     *
     * @param        $range
     * @param Carbon $date
     *
     * @return string
     * @throws FireflyException
     * @CodeCoverageIgnore
     */
    protected function periodName($range, Carbon $date)
    {
        $formatMap = [
            '1D' => 'jS F Y',
            '1W' => '\w\e\ek W, Y',
            '1M' => 'F Y',
            '1Y' => 'Y',
        ];
        if (isset($formatMap[$range])) {
            return $date->format($formatMap[$range]);
        }
        if ($range == '3M') {
            $month = intval($date->format('m'));

            return 'Q' . ceil(($month / 12) * 4) . ' ' . $date->format('Y');
        }
        if ($range == '6M') {
            $month    = intval($date->format('m'));
            $half     = ceil(($month / 12) * 2);
            $halfName = $half == 1 ? 'first' : 'second';

            return $halfName . ' half of ' . $date->format('d-m-Y');
        }
        throw new FireflyException('No _periodName() for range "' . $range . '"');
    }

    /**
     * @param        $range
     * @param Carbon $date
     *
     * @return Carbon
     * @throws FireflyException
     */
    public function previous($range, Carbon $date)
    {
        $functionMap = [
            '1D' => 'Day',
            '1W' => 'Week',
            '1M' => 'Month',
            '1Y' => 'Year'
        ];

        if (isset($functionMap[$range])) {
            $startFunction = 'startOf' . $functionMap[$range];
            $subFunction   = 'sub' . $functionMap[$range];
            $date->$startFunction()->$subFunction();

            return $date;
        }
        if ($range == '3M') {
            $date->firstOfQuarter()->subMonths(3)->firstOfQuarter();

            return $date;
        }
        if ($range == '6M') {
            $month = intval($date->format('m'));
            $date->startOfYear();
            if ($month <= 6) {
                $date->subMonths(6);
            }

            return $date;
        }
        throw new FireflyException('Cannot do _previous() on ' . $range);
    }

    /**
     * @param        $range
     * @param Carbon $date
     *
     * @return Carbon
     * @throws FireflyException
     */
    public function next($range, Carbon $date)
    {
        switch ($range) {
            case '1D':
                $date->endOfDay()->addDay();
                break;
            case '1W':
                $date->endOfWeek()->addDay()->startOfWeek();
                break;
            case '1M':
                $date->endOfMonth()->addDay()->startOfMonth();
                break;
            case '3M':
                $date->lastOfQuarter()->addDay();
                break;
            case '6M':
                if (intval($date->format('m')) >= 7) {
                    $date->startOfYear()->addYear();
                } else {
                    $date->startOfYear()->addMonths(6);
                }
                break;
            case '1Y':
                $date->startOfYear()->addYear();
                break;
            default:
                throw new FireflyException('Cannot do _next() on ' . $range);
                break;
        }

        return $date;
    }
} 
