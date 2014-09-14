<?php


namespace Firefly\Storage\RecurringTransaction;
use Illuminate\Queue\Jobs\Job;

/**
 * Interface RecurringTransactionRepositoryInterface
 *
 * @package Firefly\Storage\RecurringTransaction
 */
interface RecurringTransactionRepositoryInterface
{

    /**
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importPredictable(Job $job, array $payload);

    /**
     * @return mixed
     */
    public function get();

    /**
     * @param $name
     * @return mixed
     */
    public function findByName($name);

    /**
     * @param $data
     *
     * @return mixed
     */
    public function store($data);

    /**
     * @param \RecurringTransaction $recurringTransaction
     *
     * @return mixed
     */
    public function destroy(\RecurringTransaction $recurringTransaction);

    /**
     * @param \RecurringTransaction $recurringTransaction
     * @param                       $data
     *
     * @return mixed
     */
    public function update(\RecurringTransaction $recurringTransaction, $data);

    /**
     * @param \User $user
     * @return mixed
     */
    public function overruleUser(\User $user);


} 