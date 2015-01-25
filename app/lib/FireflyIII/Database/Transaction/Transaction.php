<?php

namespace FireflyIII\Database\Transaction;

use FireflyIII\Database\CommonDatabaseCallsInterface;
use FireflyIII\Database\CUDInterface;
use FireflyIII\Database\SwitchUser;
use FireflyIII\Exception\FireflyException;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

/**
 * Class Transaction
 *
 * @package FireflyIII\Database
 */
class Transaction implements CUDInterface, CommonDatabaseCallsInterface
{
    use SwitchUser;

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
     *
     * @return \Eloquent
     * @throws FireflyException
     */
    public function store(array $data)
    {
        $transaction = new \Transaction;
        $transaction->account()->associate($data['account']);
        $transaction->transactionJournal()->associate($data['transaction_journal']);
        $transaction->amount = floatval($data['amount']);
        if (isset($data['piggyBank'])) {
            $transaction->piggyBank()->associate($data['piggyBank']);
        }
        if (isset($data['description'])) {
            $transaction->description = $data['description'];
        }
        if ($transaction->isValid()) {
            $transaction->save();
        } else {
            throw new FireflyException($transaction->getErrors()->first());
        }

        return $transaction;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param Eloquent $model
     * @param array    $data
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
     * Validates an array. Returns an array containing MessageBags
     * errors/warnings/successes.
     *
     * @param array $model
     *
     * @return MessageBag
     */
    public function validate(array $model)
    {
        $errors = new MessageBag;
        if (is_null($model['account'])) {
            $errors->add('account', 'No account present');
        }
        if (is_null($model['transaction_journal'])) {
            $errors->add('transaction_journal', 'No valid transaction journal present');
        }

        return $errors;
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * Finds an account type using one of the "$what"'s: expense, asset, revenue, opening, etc.
     *
     * @param $what
     *
     * @return \AccountType|null
     * @throws NotImplementedException
     * @codeCoverageIgnore
     */
    public function findByWhat($what)
    {
        throw new NotImplementedException;
    }

    /**
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
