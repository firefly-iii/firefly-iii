<?php

namespace FireflyIII\Database\TransactionType;


use FireflyIII\Database\CommonDatabaseCallsInterface;
use FireflyIII\Database\CUDInterface;
use FireflyIII\Exception\FireflyException;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;


/**
 * Class TransactionType
 *
 * @package FireflyIII\Database
 */
class TransactionType implements CUDInterface, CommonDatabaseCallsInterface
{

    /**
     * @param Eloquent $model
     *
     * @return bool
     * @throws NotImplementedException
     */
    public function destroy(Eloquent $model)
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
     * @param Eloquent $model
     * @param array    $data
     *
     * @return bool
     * @throws NotImplementedException
     */
    public function update(Eloquent $model, array $data)
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
     * @param int $objectId
     *
     * @return \Eloquent
     * @throws NotImplementedException
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
     * @throws FireflyException
     */
    public function findByWhat($what)
    {
        $translation = [
            'opening'    => 'Opening balance',
            'transfer'   => 'Transfer',
            'withdrawal' => 'Withdrawal',
            'deposit'    => 'Deposit',
        ];
        if(!isset($translation[$what])) {
            throw new FireflyException('Cannot find transaction type described as "' . e($what) . '".');
        }
        return \TransactionType::whereType($translation[$what])->first();
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
