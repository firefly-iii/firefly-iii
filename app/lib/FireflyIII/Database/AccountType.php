<?php

namespace FireflyIII\Database;

use Firefly\Exception\FireflyException;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Support\Collection;
use LaravelBook\Ardent\Ardent;
use FireflyIII\Database\Ifaces\AccountTypeInterface;
use FireflyIII\Database\Ifaces\CommonDatabaseCalls;
use FireflyIII\Database\Ifaces\CUD;

/**
 * Class AccountType
 *
 * @package FireflyIII\Database
 */
class AccountType implements AccountTypeInterface, CUD, CommonDatabaseCalls
{

    /**
     * Finds an account type using one of the "$what"'s: expense, asset, revenue
     *
     * @param $what
     *
     * @return \AccountType|null
     * @throws FireflyException
     */
    public function findByWhat($what)
    {
        switch ($what) {
            case 'expense':
                return \AccountType::whereType('Expense account')->first();
                break;
            case 'asset':
                return \AccountType::whereType('Asset account')->first();
                break;
            case 'revenue':
                return \AccountType::whereType('Revenue account')->first();
                break;
            case 'initial':
                return \AccountType::whereType('Initial balance account')->first();
                break;
            default:
                throw new FireflyException('Cannot find account type described as "' . e($what) . '".');
                break;

        }
        return null;
    }

    /**
     * @param Ardent $model
     *
     * @return bool
     */
    public function destroy(Ardent $model)
    {
        // TODO: Implement destroy() method.
        throw new NotImplementedException;
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
        throw new NotImplementedException;
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
        throw new NotImplementedException;
    }

    /**
     * @param array $data
     *
     * @return Ardent
     */
    public function store(array $data)
    {
        // TODO: Implement store() method.
        throw new NotImplementedException;
    }

    /**
     * @param Ardent $model
     * @param array $data
     *
     * @return bool
     */
    public function update(Ardent $model, array $data)
    {
        // TODO: Implement update() method.
        throw new NotImplementedException;
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
        throw new NotImplementedException;
    }

    /**
     * Returns all objects.
     *
     * @return Collection
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
     */
    public function getByIds(array $ids)
    {
        // TODO: Implement getByIds() method.
        throw new NotImplementedException;
    }
}