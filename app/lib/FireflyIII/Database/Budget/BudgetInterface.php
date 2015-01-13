<?php

namespace FireflyIII\Database\Budget;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Interface BudgetInterface
 *
 * @package FireflyIII\Database
 */
interface BudgetInterface
{
    /**
     * @param \Budget $budget
     * @param Carbon  $date
     *
     * @return \LimitRepetition|null
     */
    public function repetitionOnStartingOnDate(\Budget $budget, Carbon $date);

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function expenseNoBudget(Carbon $start, Carbon $end);

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function journalsNoBudget(Carbon $start, Carbon $end);


} 
