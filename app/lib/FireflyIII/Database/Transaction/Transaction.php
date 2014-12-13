<?php

namespace FireflyIII\Database\Transaction;

use FireflyIII\Database\CommonDatabaseCalls;
use FireflyIII\Database\CUD;
use FireflyIII\Database\SwitchUser;
use FireflyIII\Exception\FireflyException;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;


/**
 * Class Transaction
 *
 * @package FireflyIII\Database
 */
class Transaction implements CUD, CommonDatabaseCalls
{
    use SwitchUser;

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
     * @throws FireflyException
     */
    public function store(array $data)
    {
        $transaction = new \Transaction;
        $transaction->account()->associate($data['account']);
        $transaction->transactionJournal()->associate($data['transaction_journal']);
        $transaction->amount = floatval($data['amount']);
        if (isset($data['piggybank'])) {
            $transaction->piggybank()->associate($data['piggybank']);
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
     */
    public function validate(array $model)
    {
        $warnings  = new MessageBag;
        $successes = new MessageBag;
        $errors    = new MessageBag;


        if (!isset($model['account_id']) && !isset($model['account'])) {
            $errors->add('account', 'No account present');
        }
        if (isset($model['account']) && !($model['account'] instanceof \Account)) {
            $errors->add('account', 'No valid account present');
        }
        if (isset($model['account_id']) && intval($model['account_id']) < 0) {
            $errors->add('account', 'No valid account_id present');
        }

        if (isset($model['piggybank_id']) && intval($model['piggybank_id']) < 0) {
            $errors->add('piggybank', 'No valid piggybank_id present');
        }

        if (!isset($model['transaction_journal_id']) && !isset($model['transaction_journal'])) {
            $errors->add('transaction_journal', 'No TJ present');
        }
        if (isset($model['transaction_journal']) && !($model['transaction_journal'] instanceof \TransactionJournal)) {
            $errors->add('transaction_journal', 'No valid transaction_journal present');
        }
        if (isset($model['transaction_journal_id']) && intval($model['transaction_journal_id']) < 0) {
            $errors->add('account', 'No valid transaction_journal_id present');
        }

        if (isset($model['description']) && strlen($model['description']) > 255) {
            $errors->add('account', 'Description too long');
        }

        if (!isset($model['amount'])) {
            $errors->add('amount', 'No amount present.');
        }
        if (isset($model['amount']) && floatval($model['amount']) == 0) {
            $errors->add('amount', 'Invalid amount.');
        }

        if (!$errors->has('account')) {
            $successes->add('account', 'OK');
        }
        if (!$errors->has('')) {
            $successes->add('piggybank', 'OK');
        }
        if (!$errors->has('transaction_journal')) {
            $successes->add('transaction_journal', 'OK');
        }
        if (!$errors->has('amount')) {
            $successes->add('amount', 'OK');
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'successes' => $successes];
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
     * @throws NotImplementedException
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