<?php

namespace FireflyIII\Shared\Toolkit;

use Carbon\Carbon;
use FireflyIII\Exception\FireflyException;

/**
 * Class Date
 *
 * @package FireflyIII\Shared\Toolkit
 */
class Date
{
    /**
     * @param Carbon         $theDate
     * @param                $repeatFreq
     * @param                $skip
     *
     * @return \Carbon\Carbon
     * @throws FireflyException
     */
    public function addPeriod(Carbon $theDate, $repeatFreq, $skip)
    {
        $date = clone $theDate;
        $add  = ($skip + 1);

        $functionMap = [
            'daily'     => 'addDays',
            'weekly'    => 'addWeeks',
            'week'      => 'addWeeks',
            'month'     => 'addMonths',
            'monthly'   => 'addMonths',
            'quarter'   => 'addMonths',
            'quarterly' => 'addMonths',
            'half-year' => 'addMonths',
            'year'      => 'addYears',
            'yearly'    => 'addYears',
        ];
        $modifierMap = [
            'quarter'   => 3,
            'quarterly' => 3,
            'half-year' => 6,
        ];
        if (!isset($functionMap[$repeatFreq])) {
            throw new FireflyException('Cannot do addPeriod for $repeat_freq "' . $repeatFreq . '"');
        }
        if (isset($modifierMap[$repeatFreq])) {
            $add = $add * $modifierMap[$repeatFreq];
        }
        $function = $functionMap[$repeatFreq];
        $date->$function($add);

        return $date;
    }

    /**
     * @param Carbon         $theCurrentEnd
     * @param                $repeatFreq
     *
     * @return Carbon
     * @throws FireflyException
     */
    public function endOfPeriod(Carbon $theCurrentEnd, $repeatFreq)
    {
        $currentEnd = clone $theCurrentEnd;

        $functionMap = [
            'daily'     => 'addDay',
            'week'      => 'addWeek',
            'weekly'    => 'addWeek',
            'month'     => 'addMonth',
            'monthly'   => 'addMonth',
            'quarter'   => 'addMonths',
            'quarterly' => 'addMonths',
            'half-year' => 'addMonths',
            'year'      => 'addYear',
            'yearly'    => 'addYear',
        ];
        $modifierMap = [
            'quarter'   => 3,
            'quarterly' => 3,
            'half-year' => 6,
        ];

        $subDay = ['week', 'weekly', 'month', 'monthly', 'quarter', 'quarterly', 'half-year', 'year', 'yearly'];

        if (!isset($functionMap[$repeatFreq])) {
            throw new FireflyException('Cannot do endOfPeriod for $repeat_freq ' . $repeatFreq);
        }
        $function = $functionMap[$repeatFreq];
        if (isset($modifierMap[$repeatFreq])) {
            $currentEnd->$function($modifierMap[$repeatFreq]);
        } else {
            $currentEnd->$function();
        }
        if (in_array($repeatFreq, $subDay)) {
            $currentEnd->subDay();
        }

        return $currentEnd;
    }

    /**
     * @param Carbon         $theCurrentEnd
     * @param                $repeatFreq
     * @param Carbon         $maxDate
     *
     * @return Carbon
     * @throws FireflyException
     */
    public function endOfX(Carbon $theCurrentEnd, $repeatFreq, Carbon $maxDate)
    {
        $currentEnd = clone $theCurrentEnd;
        switch ($repeatFreq) {
            default:
                throw new FireflyException('Cannot do endOfPeriod for $repeat_freq ' . $repeatFreq);
                break;
            case 'daily':
                $currentEnd->endOfDay();
                break;
            case 'week':
            case 'weekly':
                $currentEnd->endOfWeek();
                break;
            case 'month':
            case 'monthly':
                $currentEnd->endOfMonth();
                break;
            case 'quarter':
            case 'quarterly':
                $currentEnd->lastOfQuarter();
                break;
            case 'half-year':
                $month = intval($theCurrentEnd->format('m'));
                $currentEnd->endOfYear();
                if ($month <= 6) {
                    $currentEnd->subMonths(6);
                }
                break;
            case 'year':
            case 'yearly':
                $currentEnd->endOfYear();
                break;
        }
        if ($currentEnd > $maxDate) {
            return clone $maxDate;
        }

        return $currentEnd;
    }

    /**
     * @param Carbon         $date
     * @param                $repeatFrequency
     *
     * @return string
     * @throws FireflyException
     */
    public function periodShow(Carbon $date, $repeatFrequency)
    {
        switch ($repeatFrequency) {
            default:
                throw new FireflyException('No date formats for frequency "' . $repeatFrequency . '"!');
                break;
            case 'daily':
                return $date->format('j F Y');
                break;
            case 'week':
            case 'weekly':
                return $date->format('\W\e\e\k W, Y');
                break;
            case 'quarter':
                return $date->format('F Y');
                break;
            case 'monthly':
            case 'month':
                return $date->format('F Y');
                break;
            case 'year':
            case 'yearly':
                return $date->format('Y');
                break;
        }
    }

    /**
     * @param Carbon         $theDate
     * @param                $repeatFreq
     *
     * @return Carbon
     * @throws FireflyException
     */
    public function startOfPeriod(Carbon $theDate, $repeatFreq)
    {
        $date = clone $theDate;
        switch ($repeatFreq) {
            default:
                throw new FireflyException('Cannot do startOfPeriod for $repeat_freq ' . $repeatFreq);
                break;
            case 'daily':
                $date->startOfDay();
                break;
            case 'week':
            case 'weekly':
                $date->startOfWeek();
                break;
            case 'month':
            case 'monthly':
                $date->startOfMonth();
                break;
            case 'quarter':
            case 'quarterly':
                $date->firstOfQuarter();
                break;
            case 'half-year':
                $month = intval($date->format('m'));
                $date->startOfYear();
                if ($month >= 7) {
                    $date->addMonths(6);
                }
                break;
            case 'year':
            case 'yearly':
                $date->startOfYear();
                break;
        }

        return $date;
    }

    /**
     * @param Carbon         $theDate
     * @param                $repeatFreq
     * @param int            $subtract
     *
     * @return Carbon
     * @throws FireflyException
     */
    public function subtractPeriod(Carbon $theDate, $repeatFreq, $subtract = 1)
    {
        $date = clone $theDate;
        switch ($repeatFreq) {
            default:
                throw new FireflyException('Cannot do subtractPeriod for $repeat_freq ' . $repeatFreq);
                break;
            case 'day':
            case 'daily':
                $date->subDays($subtract);
                break;
            case 'week':
            case 'weekly':
                $date->subWeeks($subtract);
                break;
            case 'month':
            case 'monthly':
                $date->subMonths($subtract);
                break;
            case 'quarter':
            case 'quarterly':
                $months = $subtract * 3;
                $date->subMonths($months);
                break;
            case 'half-year':
                $months = $subtract * 6;
                $date->subMonths($months);
                break;
            case 'year':
            case 'yearly':
                $date->subYears($subtract);
                break;
        }

        return $date;
    }
}

