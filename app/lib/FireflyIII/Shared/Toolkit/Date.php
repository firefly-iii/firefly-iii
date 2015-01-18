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
     * @SuppressWarnings("CyclomaticComplexity") // It's exactly 5. So I don't mind.
     *
     * @param Carbon         $theCurrentEnd
     * @param                $repeatFreq
     * @param Carbon         $maxDate
     *
     * @return Carbon
     * @throws FireflyException
     */
    public function endOfX(Carbon $theCurrentEnd, $repeatFreq, Carbon $maxDate)
    {
        $functionMap = [
            'daily'     => 'endOfDay',
            'week'      => 'endOfWeek',
            'weekly'    => 'endOfWeek',
            'month'     => 'endOfMonth',
            'monthly'   => 'endOfMonth',
            'quarter'   => 'lastOfQuarter',
            'quarterly' => 'lastOfQuarter',
            'year'      => 'endOfYear',
            'yearly'    => 'endOfYear',
        ];
        $specials    = ['mont', 'monthly'];

        $currentEnd = clone $theCurrentEnd;

        if (isset($functionMap[$repeatFreq])) {
            $function = $functionMap[$repeatFreq];
            $currentEnd->$function();

        }
        if (isset($specials[$repeatFreq])) {
            $month = intval($theCurrentEnd->format('m'));
            $currentEnd->endOfYear();
            if ($month <= 6) {
                $currentEnd->subMonths(6);
            }
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
        $formatMap = [
            'daily'   => 'j F Y',
            'week'    => '\W\e\e\k W, Y',
            'weekly'  => '\W\e\e\k W, Y',
            'quarter' => 'F Y',
            'month'   => 'F Y',
            'monthly' => 'F Y',
            'year'    => 'Y',
            'yearly'  => 'Y',

        ];
        if (isset($formatMap[$repeatFrequency])) {
            return $date->format($formatMap[$repeatFrequency]);
        }
        throw new FireflyException('No date formats for frequency "' . $repeatFrequency . '"!');
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

