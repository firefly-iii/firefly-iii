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
     * @param Carbon $date
     * @param        $repeatFreq
     * @param        $skip
     *
     * @return Carbon
     * @throws FireflyException
     */
    public function addPeriod(Carbon $date, $repeatFreq, $skip)
    {
        $add = ($skip + 1);
        switch ($repeatFreq) {
            default:
                throw new FireflyException('Cannot do addPeriod for $repeat_freq ' . $repeatFreq);
                break;
            case 'daily':
                $date->addDays($add);
                break;
            case 'weekly':
                $date->addWeeks($add);
                break;
            case 'monthly':
                $date->addMonths($add);
                break;
            case 'quarterly':
                $months = $add * 3;
                $date->addMonths($months);
                break;
            case 'half-year':
                $months = $add * 6;
                $date->addMonths($months);
                break;
            case 'yearly':
                $date->addYears($add);
                break;
        }

        return $date;
    }

    /**
     * @param Carbon $currentEnd
     * @param        $repeatFreq
     *
     * @throws FireflyException
     */
    public function endOfPeriod(Carbon $currentEnd, $repeatFreq)
    {
        switch ($repeatFreq) {
            default:
                throw new FireflyException('Cannot do endOfPeriod for $repeat_freq ' . $repeatFreq);
                break;
            case 'daily':
                $currentEnd->addDay();
                break;
            case 'weekly':
                $currentEnd->addWeek()->subDay();
                break;
            case 'monthly':
                $currentEnd->addMonth()->subDay();
                break;
            case 'quarterly':
                $currentEnd->addMonths(3)->subDay();
                break;
            case 'half-year':
                $currentEnd->addMonths(6)->subDay();
                break;
            case 'yearly':
                $currentEnd->addYear()->subDay();
                break;
        }
    }

    /**
     * @param Carbon $date
     * @param        $repeatFreq
     *
     * @return Carbon
     * @throws FireflyException
     */
    public function startOfPeriod(Carbon $date, $repeatFreq)
    {
        switch ($repeatFreq) {
            default:
                throw new FireflyException('Cannot do startOfPeriod for $repeat_freq ' . $repeatFreq);
                break;
            case 'daily':
                $date->startOfDay();
                break;
            case 'weekly':
                $date->startOfWeek();
                break;
            case 'monthly':
                $date->startOfMonth();
                break;
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
            case 'yearly':
                $date->startOfYear();
                break;
        }

        return $date;
    }
} 