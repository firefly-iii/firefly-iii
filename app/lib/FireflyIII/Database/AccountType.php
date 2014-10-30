<?php

namespace FireflyIII\Database;

use Firefly\Exception\FireflyException;
use Illuminate\Support\Collection;
use LaravelBook\Ardent\Ardent;
use FireflyIII\Database\Ifaces\AccountTypeInterface;
use FireflyIII\Database\Ifaces\CommonDatabaseCalls;
use FireflyIII\Database\Ifaces\CUD;
use FireflyIII\Database\Ifaces\AccountTypeInterface;

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
     * @param array $ids
     *
     * @return Collection
     */
    public function getByIds(array $ids)
    {
        // TODO: Implement getByIds() method.
    }
}