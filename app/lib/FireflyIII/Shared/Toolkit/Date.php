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
     * @param Carbon $theDate
     * @param        $repeatFreq
     * @param        $skip
     *
     * @return Carbon
     * @throws FireflyException
     */
    public function addPeriod(Carbon $theDate, $repeatFreq, $skip)
    {
        $date = clone $theDate;
        $add  = ($skip + 1);
        switch ($repeatFreq) {
            default:
                throw new FireflyException('Cannot do addPeriod for $repeat_freq ' . $repeatFreq);
                break;
            case 'daily':
                $date->addDays($add);
                break;
            case 'week':
            case 'weekly':
                $date->addWeeks($add);
                break;
            case 'month':
            case 'monthly':
                $date->addMonths($add);
                break;
            case 'quarter':
            case 'quarterly':
                $months = $add * 3;
                $date->addMonths($months);
                break;
            case 'half-year':
                $months = $add * 6;
                $date->addMonths($months);
                break;
            case 'year':
            case 'yearly':
                $date->addYears($add);
                break;
        }

        return $date;
    }

    /**
     * @param Carbon $theCurrentEnd
     * @param        $repeatFreq
     *
     * @return mixed
     * @throws FireflyException
     */
    public function endOfPeriod(Carbon $theCurrentEnd, $repeatFreq)
    {
        $currentEnd = clone $theCurrentEnd;
        switch ($repeatFreq) {
            default:
                throw new FireflyException('Cannot do endOfPeriod for $repeat_freq ' . $repeatFreq);
                break;
            case 'daily':
                $currentEnd->addDay();
                break;
            case 'week':
            case 'weekly':
                $currentEnd->addWeek()->subDay();
                break;
            case 'month':
            case 'monthly':
                $currentEnd->addMonth()->subDay();
                break;
            case 'quarter':
            case 'quarterly':
                $currentEnd->addMonths(3)->subDay();
                break;
            case 'half-year':
                $currentEnd->addMonths(6)->subDay();
                break;
            case 'year':
            case 'yearly':
                $currentEnd->addYear()->subDay();
                break;
        }

        return $currentEnd;
    }

    public function periodShow(Carbon $date, $repeatFrequency)
    {
        switch ($repeatFrequency) {
            default:
                throw new FireflyException('No date formats for frequency "' . $repeatFrequency . '"!');
                break;
            case 'daily':
                return $date->format('j F Y');
                break;
            case 'weekly':
                return $date->format('\W\e\e\k W, Y');
                break;
            case 'monthly':
            case 'month':
                return $date->format('F Y');
                break;
            case 'yearly':
                return $date->format('Y');
                break;
        }
    }

    /**
     * @param Carbon $theDate
     * @param        $repeatFreq
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
     * @param Carbon $date
     * @param        $repeatFreq
     * @param int    $subtract
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
            case 'daily':
                $date->subDays($subtract);
                break;
            case 'weekly':
                $date->subWeeks($subtract);
                break;
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

