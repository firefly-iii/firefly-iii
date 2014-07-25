<?php

namespace Firefly\Storage\Limit;

use Carbon\Carbon;

/**
 * Interface LimitRepositoryInterface
 *
 * @package Firefly\Storage\Limit
 */
interface LimitRepositoryInterface
{

    /**
     * @param $data
     *
     * @return mixed
     */
    public function store($data);

    /**
     * @param \Budget $budget
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return mixed
     */
    public function getTJByBudgetAndDateRange(\Budget $budget, Carbon $start, Carbon $end);

    /**
     * @param $limitId
     *
     * @return mixed
     */
    public function find($limitId);
} 