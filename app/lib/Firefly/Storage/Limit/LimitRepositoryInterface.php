<?php

namespace Firefly\Storage\Limit;

use Carbon\Carbon;
use Illuminate\Queue\Jobs\Job;

/**
 * Interface LimitRepositoryInterface
 *
 * @package Firefly\Storage\Limit
 */
interface LimitRepositoryInterface
{

    /**
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importLimit(Job $job, array $payload);

    /**
     * @param \Limit $limit
     *
     * @return mixed
     */
    public function destroy(\Limit $limit);


    /**
     * @param \Budget $budget
     * @param Carbon  $date
     *
     * @return mixed
     */
    public function findByBudgetAndDate(\Budget $budget, Carbon $date);

    /**
     * @param \Budget $budget
     * @param Carbon  $start
     * @param Carbon  $end
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

    /**
     * @param \User $user
     *
     * @return mixed
     */
    public function overruleUser(\User $user);
} 