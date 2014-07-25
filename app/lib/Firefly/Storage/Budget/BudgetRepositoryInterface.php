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
     * @return mixed
     */
    public function getAsSelectList();

    /**
     * @return mixed
     */
    public function get();

    /**
     * @param $data
     *
     * @return mixed
     */
    public function store($data);

    /**
     * @param $budgetId
     *
     * @return mixed
     */
    public function find($budgetId);

    /**
     * @param Carbon $date
     * @param        $range
     *
     * @return mixed
     */
    public function getWithRepetitionsInPeriod(Carbon $date, $range);

} 