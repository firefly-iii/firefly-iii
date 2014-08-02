<?php

namespace Firefly\Storage\Budget;

use Carbon\Carbon;

/**
 * Interface BudgetRepositoryInterface
 *
 * @package Firefly\Storage\Budget
 */
interface BudgetRepositoryInterface
{
    /**
     * @param $data
     *
     * @return mixed
     */
    public function destroy(\Budget $budget);

    /**
     * @param $budgetId
     *
     * @return mixed
     */
    public function find($budgetId);

    /**
     * @return mixed
     */
    public function get();

    /**
     * @return mixed
     */
    public function getAsSelectList();

    /**
     * @param Carbon $date
     * @param        $range
     *
     * @return mixed
     */
    public function getWithRepetitionsInPeriod(Carbon $date, $range);

    /**
     * @param $data
     *
     * @return mixed
     */
    public function store($data);

    /**
     * @param $data
     *
     * @return mixed
     */
    public function update(\Budget $budget, $data);

} 