<?php

namespace Firefly\Storage\Budget;
use Illuminate\Queue\Jobs\Job;

/**
 * Interface BudgetRepositoryInterface
 *
 * @package Firefly\Storage\Budget
 */
interface BudgetRepositoryInterface
{
    /**
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importBudget(Job $job, array $payload);

    /**
     * Takes a transaction/budget component and updates the transaction journal to match.
     *
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importUpdateTransaction(Job $job, array $payload);

    /**
     * Takes a transfer/budget component and updates the transaction journal to match.
     *
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importUpdateTransfer(Job $job, array $payload);

    /**
     * @param \Budget $budget
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
     * @param $budgetName
     * @return mixed
     */
    public function findByName($budgetName);

    /**
     * @param \User $user
     * @return mixed
     */
    public function overruleUser(\User $user);

    /**
     * @return mixed
     */
    public function get();

    /**
     * @return mixed
     */
    public function getAsSelectList();

    /**
     * @param $data
     *
     * @return mixed
     */
    public function store($data);

    /**
     * @param \Budget $budget
     * @param         $data
     *
     * @return mixed
     */
    public function update(\Budget $budget, $data);

} 