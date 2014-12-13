<?php

namespace FireflyIII\Database\TransactionType;


use FireflyIII\Database\CommonDatabaseCalls;
use FireflyIII\Database\CUD;
use FireflyIII\Exception\FireflyException;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Support\Collection;


/**
 * Class TransactionType
 *
 * @package FireflyIII\Database
 */
class TransactionType implements CUD, CommonDatabaseCalls
{

    /**
     * @param \Eloquent $model
     *
     * @return bool
     * @throws NotImplementedException
     */
    public function destroy(\Eloquent $model)
    {
        // TODO: Implement destroy() method.
        throw new NotImplementedException;
    }

    /**
     * @param array $data
     *
     * @return \Eloquent
     * @throws NotImplementedException
     */
    public function store(array $data)
    {
        // TODO: Implement store() method.
        throw new NotImplementedException;
    }

    /**
     * @param \Eloquent $model
     * @param array     $data
     *
     * @return bool
     * @throws NotImplementedException
     */
    public function update(\Eloquent $model, array $data)
    {
        // TODO: Implement update() method.
        throw new NotImplementedException;
    }

    /**
     * Validates an array. Returns an array containing MessageBags
     * errors/warnings/successes.
     *
     * @param array $model
     *
     * @return array
     * @throws NotImplementedException
     */
    public function validate(array $model)
    {
        // TODO: Implement validate() method.
        throw new NotImplementedException;
    }

    /**
     * Returns an object with id $id.
     *
     * @param int $id
     *
     * @return \Eloquent
     * @throws NotImplementedException
     */
    public function find($id)
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
     * @throws FireflyException
     */
    public function findByWhat($what)
    {
        switch ($what) {
            case 'opening':
                return \TransactionType::whereType('Opening balance')->first();
                break;
            case 'transfer':
                return \TransactionType::whereType('Transfer')->first();
                break;
            case 'withdrawal':
                return \TransactionType::whereType('Withdrawal')->first();
                break;
            case 'deposit':
                return \TransactionType::whereType('Deposit')->first();
                break;
            default:
                throw new FireflyException('Cannot find transaction type described as "' . e($what) . '".');
                break;


        }
    }

    /**
     * Returns all objects.
     *
     * @return Collection
     * @throws NotImplementedException
     */
    public function get()
    {
        // TODO: Implement get() method.
        throw new NotImplementedException;
    }

    /**
     * @param array $ids
     *
     * @return Collection
     * @throws NotImplementedException
     */
    public function getByIds(array $ids)
    {
        // TODO: Implement getByIds() method.
        throw new NotImplementedException;
    }
}