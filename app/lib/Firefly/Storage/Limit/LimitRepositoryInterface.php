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
     * @param \Limit $limit
     *
     * @return mixed
     */
    public function destroy(\Limit $limit);

    /**
     * @param $limitId
     *
     * @return mixed
     */
    public function find($limitId);

    /**
     * @param \Budget $budget
     * @param Carbon $date
     * @return mixed
     */
    public function findByBudgetAndDate(\Budget $budget, Carbon $date);

    /**
     * @param \Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return mixed
     */
    public function getTJByBudgetAndDateRange(\Budget $budget, Carbon $start, Carbon $end);

    /**
     * @param $data
     *
     * @return mixed
     */
    public function store($data);

    /**
     * @param \Limit $limit
     * @param        $data
     *
     * @return mixed
     */
    public function update(\Limit $limit, $data);
} 