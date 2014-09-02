<?php

namespace Firefly\Storage\Transaction;

/**
 * Class EloquentTransactionRepository
 *
 * @package Firefly\Storage\Transaction
 */
class EloquentTransactionRepository implements TransactionRepositoryInterface
{
    protected $_user = null;

    /**
     *
     */
    public function __construct()
    {
        $this->_user = \Auth::user();
    }

    /**
     * @param \User $user
     * @return mixed|void
     */
    public function overruleUser(\User $user)
    {
        $this->_user = $user;
        return true;
    }

}