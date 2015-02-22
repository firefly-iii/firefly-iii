<?php

namespace FireflyIII\Repositories\Budget;

use Carbon\Carbon;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\LimitRepetition;

/**
 * Class BudgetRepository
 *
 * @package FireflyIII\Repositories\Budget
 */
class BudgetRepository implements BudgetRepositoryInterface
{

    /**
     * @param Budget $budget
     * @param Carbon $date
     *
     * @return float
     */
    public function spentInMonth(Budget $budget, Carbon $date)
    {
        $end = clone $date;
        $date->startOfMonth();
        $end->endOfMonth();
        $sum = floatval($budget->transactionjournals()->before($end)->after($date)->lessThan(0)->sum('amount')) * -1;

        return $sum;
    }

    /**
     * @param Budget $budget
     * @param Carbon $date
     * @param        $amount
     *
     * @return LimitRepetition|null
     */
    public function updateLimitAmount(Budget $budget, Carbon $date, $amount)
    {
        /** @var BudgetLimit $limit */
        $limit = $budget->limitrepetitions()->where('limit_repetitions.startdate', $date)->first(['limit_repetitions.*']);
        if (!$limit) {
            // create one!
            $limit = new BudgetLimit;
            $limit->budget()->associate($budget);
            $limit->startdate   = $date;
            $limit->amount      = $amount;
            $limit->repeat_freq = 'monthly';
            $limit->repeats     = 0;
            $limit->save();
        } else {
            if ($amount > 0) {
                $limit->amount = $amount;
                $limit->save();
            } else {
                $limit->delete();
            }
        }
        return $limit;


    }
}