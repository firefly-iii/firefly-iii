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


} 