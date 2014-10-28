<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 24/10/14
 * Time: 10:27
 */

namespace FireflyIII\Database;


use Illuminate\Support\Collection;
use LaravelBook\Ardent\Ardent;

/**
 * Class TransactionType
 *
 * @package FireflyIII\Database
 */
class TransactionCurrency implements TransactionCurrencyInterface, CUD, CommonDatabaseCalls
{

    /**
     * @param Ardent $model
     *
     * @return bool
     */
    public function destroy(Ardent $model)
    {
        // TODO: Implement destroy() method.
    }

    /**
     * Validates a model. Returns an array containing MessageBags
     * errors/warnings/successes.
     *
     * @param Ardent $model
     *
     * @return array
     */
    public function validateObject(Ardent $model)
    {
        // TODO: Implement validateObject() method.
    }

    /**
     * Validates an array. Returns an array containing MessageBags
     * errors/warnings/successes.
     *
     * @param array $model
     *
     * @return array
     */
    public function validate(array $model)
    {
        // TODO: Implement validate() method.
    }

    /**
     * @param array $data
     *
     * @return Ardent
     */
    public function store(array $data)
    {
        // TODO: Implement store() method.
    }

    /**
     * Returns an object with id $id.
     *
     * @param int $id
     *
     * @return Ardent
     */
    public function find($id)
    {
        // TODO: Implement find() method.
    }

    /**
     * Returns all objects.
     *
     * @return Collection
     */
    public function get()
    {
        // TODO: Implement get() method.
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
        // TODO: Implement get() method.
    }

    /**
     * @param string $code
     *
     * @return \TransactionCurrency|null
     */
    public function findByCode($code)
    {
        return \TransactionCurrency::whereCode($code)->first();
    }
}