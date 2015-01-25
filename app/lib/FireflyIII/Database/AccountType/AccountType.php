<?php

namespace FireflyIII\Database\AccountType;

use FireflyIII\Database\CommonDatabaseCallsInterface;
use FireflyIII\Database\CUDInterface;
use FireflyIII\Exception\FireflyException;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;

/**
 * Class AccountType
 *
 * @package FireflyIII\Database
 */
class AccountType implements CUDInterface, CommonDatabaseCallsInterface
{

    /**
     * @param Eloquent $model
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return bool
     * @throws NotImplementedException
     * @codeCoverageIgnore
     */
    public function destroy(Eloquent $model)
    {
        throw new NotImplementedException;
    }

    /**
     * @param array $data
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return \Eloquent
     * @throws NotImplementedException
     * @codeCoverageIgnore
     */
    public function store(array $data)
    {
        throw new NotImplementedException;
    }

    /**
     * @param Eloquent $model
     * @param array    $data
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return bool
     * @throws NotImplementedException
     * @codeCoverageIgnore
     */
    public function update(Eloquent $model, array $data)
    {
        throw new NotImplementedException;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * Validates an array. Returns an array containing MessageBags
     * errors/warnings/successes.
     *
     * @param array $model
     *
     * @return array
     * @throws NotImplementedException
     * @codeCoverageIgnore
     */
    public function validate(array $model)
    {
        throw new NotImplementedException;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * Returns an object with id $id.
     *
     * @param int $objectId
     *
     * @return \Eloquent
     * @throws NotImplementedException
     * @codeCoverageIgnore
     */
    public function find($objectId)
    {
        throw new NotImplementedException;
    }

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
        $typeMap = [
            'expense' => 'Expense account',
            'asset'   => 'Asset account',
            'revenue' => 'Revenue account',
            'cash'    => 'Cash account',
            'initial' => 'Initial balance account',
        ];
        if (isset($typeMap[$what])) {
            return \AccountType::whereType($typeMap[$what])->first();
        }
        throw new FireflyException('Cannot find account type described as "' . e($what) . '".');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * Returns all objects.
     *
     * @return Collection
     * @throws NotImplementedException
     * @codeCoverageIgnore
     */
    public function get()
    {
        throw new NotImplementedException;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param array $ids
     *
     * @return Collection
     * @throws NotImplementedException
     * @codeCoverageIgnore
     */
    public function getByIds(array $ids)
    {
        throw new NotImplementedException;
    }
}
