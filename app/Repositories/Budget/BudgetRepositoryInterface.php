<?php

namespace FireflyIII\Repositories\Budget;
use FireflyIII\Models\Budget;
use FireflyIII\Models\LimitRepetition;
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
     *
     * @return boolean
     */
    public function destroy(Budget $budget);

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

    /**
     * @param array $data
     *
     * @return Budget
     */
    public function store(array $data);

    /**
     * @param Budget $budget
     * @param array   $data
     *
     * @return Budget
     */
    public function update(Budget $budget, array $data);

    /**
     * Returns all the transaction journals for a limit, possibly limited by a limit repetition.
     *
     * @param Budget          $budget
     * @param LimitRepetition $repetition
     * @param int              $take
     *
     * @return \Illuminate\Pagination\Paginator
     */
    public function getJournals(Budget $budget, LimitRepetition $repetition = null, $take = 50);

}