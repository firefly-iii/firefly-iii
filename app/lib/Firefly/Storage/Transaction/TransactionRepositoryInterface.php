<?php

namespace Firefly\Storage\Transaction;

/**
 * Interface TransactionRepositoryInterface
 *
 * @package Firefly\Storage\Transaction
 */
interface TransactionRepositoryInterface
{
    /**
     * @param \User $user
     * @return mixed
     */
    public function overruleUser(\User $user);


} 