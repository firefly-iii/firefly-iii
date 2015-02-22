<?php

namespace FireflyIII\Repositories\Budget;
use FireflyIII\Models\Budget;
use Carbon\Carbon;
/**
 * Interface BudgetRepositoryInterface
 *
 * @package FireflyIII\Repositories\Budget
 */
interface BudgetRepositoryInterface
{

    /**
     * @param Budget $budget
     * @param Carbon $date
     *
     * @return float
     */
    public function spentInMonth(Budget $budget, Carbon $date);

    /**
     * @param Budget $budget
     * @param Carbon $date
     * @param        $amount
     *
     * @return mixed
     */
    public function updateLimitAmount(Budget $budget, Carbon $date, $amount);

}