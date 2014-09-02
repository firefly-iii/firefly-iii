<?php


namespace Firefly\Storage\RecurringTransaction;

/**
 * Interface RecurringTransactionRepositoryInterface
 *
 * @package Firefly\Storage\RecurringTransaction
 */
interface RecurringTransactionRepositoryInterface
{

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