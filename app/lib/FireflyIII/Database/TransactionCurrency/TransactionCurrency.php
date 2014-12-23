<?php

namespace FireflyIII\Database\TransactionCurrency;
use FireflyIII\Database\CommonDatabaseCalls;
use Illuminate\Support\Collection;
use FireflyIII\Exception\NotImplementedException;

/**
 * Class TransactionType
 *
 * @package FireflyIII\Database
 */
class TransactionCurrency implements TransactionCurrencyInterface, CommonDatabaseCalls
{

    /**
     * @param string $code
     *
     * @return \TransactionCurrency|null
     */
    public function findByCode($code)
    {
        return \TransactionCurrency::whereCode($code)->first();
    }

    /**
     * Returns an object with id $id.
     *
     * @param int $objectId
     *
     * @return \Eloquent
     */
    public function find($objectId)
    {
        // TODO: Implement find() method.
        throw new NotImplementedException;
    }

    /**
     * Finds an account type using one of the "$what"'s: expense, asset, revenue, opening, etc.
     *
     * @param $what
     *
     * @return \AccountType|null
     */
    public function findByWhat($what)
    {
        // TODO: Implement findByWhat() method.
        throw new NotImplementedException;
    }

    /**
     * Returns all objects.
     *
     * @return Collection
     */
    public function get()
    {
        return \TransactionCurrency::orderBy('code','ASC')->get();
    }

    /**
     * @param array $objectIds
     *
     * @return Collection
     */
    public function getByIds(array $objectIds)
    {
        // TODO: Implement getByIds() method.
        throw new NotImplementedException;
    }
}