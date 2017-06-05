<?php
/**
 * JournalUpdate.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\Journal;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class JournalUpdate
 *
 * @package FireflyIII\Repositories\Journal
 */
class JournalUpdate implements JournalUpdateInterface
{
    /** @var User */
    private $user;
    /** @var array */
    private $validMetaFields = ['interest_date', 'book_date', 'process_date', 'due_date', 'payment_date', 'invoice_date', 'internal_reference', 'notes'];

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     */
    public function update(TransactionJournal $journal, array $data): TransactionJournal
    {

        // update actual journal:
        $journal->description   = $data['description'];
        $journal->date          = $data['date'];
        $accounts               = JournalSupport::storeAccounts($this->user, $journal->transactionType, $data);
        $data                   = JournalSupport::verifyNativeAmount($data, $accounts);
        $data['amount']         = strval($data['amount']);
        $data['foreign_amount'] = is_null($data['foreign_amount']) ? null : strval($data['foreign_amount']);

        // unlink all categories, recreate them:
        $journal->categories()->detach();
        $journal->budgets()->detach();

        JournalSupport::storeCategoryWithJournal($journal, $data['category']);
        JournalSupport::storeBudgetWithJournal($journal, $data['budget_id']);

        // negative because source loses money.
        $this->updateSourceTransaction($journal, $accounts['source'], $data);

        // positive because destination gets money.
        $this->updateDestinationTransaction($journal, $accounts['destination'], $data);

        $journal->save();

        // update tags:
        if (isset($data['tags']) && is_array($data['tags'])) {
            JournalSupport::updateTags($journal, $data['tags']);
        }

        // update meta fields:
        $result = $journal->save();
        if ($result) {
            foreach ($data as $key => $value) {
                if (in_array($key, $this->validMetaFields)) {
                    $journal->setMeta($key, $value);
                    continue;
                }
                Log::debug(sprintf('Could not store meta field "%s" with value "%s" for journal #%d', json_encode($key), json_encode($value), $journal->id));
            }

            return $journal;
        }

        return $journal;
    }

    /**
     * Same as above but for transaction journal with multiple transactions.
     *
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     */
    public function updateSplitJournal(TransactionJournal $journal, array $data): TransactionJournal
    {
        // update actual journal:
        $journal->description = $data['journal_description'];
        $journal->date        = $data['date'];
        $journal->save();
        Log::debug(sprintf('Updated split journal #%d', $journal->id));

        // unlink all categories:
        $journal->categories()->detach();
        $journal->budgets()->detach();

        // update meta fields:
        $result = $journal->save();
        if ($result) {
            foreach ($data as $key => $value) {
                if (in_array($key, $this->validMetaFields)) {
                    $journal->setMeta($key, $value);
                    continue;
                }
                Log::debug(sprintf('Could not store meta field "%s" with value "%s" for journal #%d', json_encode($key), json_encode($value), $journal->id));
            }
        }


        // update tags:
        if (isset($data['tags']) && is_array($data['tags'])) {
            JournalSupport::updateTags($journal, $data['tags']);
        }

        // delete original transactions, and recreate them.
        $journal->transactions()->delete();

        // store each transaction.
        $identifier = 0;
        Log::debug(sprintf('Count %d transactions in updateSplitJournal()', count($data['transactions'])));

        foreach ($data['transactions'] as $transaction) {
            Log::debug(sprintf('Split journal update split transaction %d', $identifier));
            $transaction = $this->appendTransactionData($transaction, $data);
            $this->storeSplitTransaction($journal, $transaction, $identifier);
            $identifier++;
        }

        $journal->save();

        return $journal;
    }

    /**
     * When the user edits a split journal, each line is missing crucial data:
     *
     * - Withdrawal lines are missing the source account ID
     * - Deposit lines are missing the destination account ID
     * - Transfers are missing both.
     *
     * We need to append the array.
     *
     * @param array $transaction
     * @param array $data
     *
     * @return array
     */
    private function appendTransactionData(array $transaction, array $data): array
    {
        switch ($data['what']) {
            case strtolower(TransactionType::TRANSFER):
            case strtolower(TransactionType::WITHDRAWAL):
                $transaction['source_account_id'] = intval($data['journal_source_account_id']);
                break;
        }

        switch ($data['what']) {
            case strtolower(TransactionType::TRANSFER):
            case strtolower(TransactionType::DEPOSIT):
                $transaction['destination_account_id'] = intval($data['journal_destination_account_id']);
                break;
        }

        return $transaction;
    }

    /**
     * @param TransactionJournal $journal
     * @param array              $transaction
     * @param int                $identifier
     *
     * @return Collection
     */
    private function storeSplitTransaction(TransactionJournal $journal, array $transaction, int $identifier): Collection
    {
        // store source and destination accounts (depends on type)
        $accounts = JournalSupport::storeAccounts($this->user, $journal->transactionType, $transaction);

        // store transaction one way:
        $amount        = bcmul(strval($transaction['amount']), '-1');
        $foreignAmount = is_null($transaction['foreign_amount']) ? null : bcmul(strval($transaction['foreign_amount']), '-1');
        $one           = JournalSupport::storeTransaction(
            [
                'journal'                 => $journal,
                'account'                 => $accounts['source'],
                'amount'                  => $amount,
                'transaction_currency_id' => $transaction['transaction_currency_id'],
                'foreign_amount'          => $foreignAmount,
                'foreign_currency_id'     => $transaction['foreign_currency_id'],
                'description'             => $transaction['description'],
                'category'                => null,
                'budget'                  => null,
                'identifier'              => $identifier,
            ]
        );
        JournalSupport::storeCategoryWithTransaction($one, $transaction['category']);
        JournalSupport::storeBudgetWithTransaction($one, $transaction['budget_id']);

        // and the other way:
        $amount        = strval($transaction['amount']);
        $foreignAmount = is_null($transaction['foreign_amount']) ? null : strval($transaction['foreign_amount']);
        $two           = JournalSupport::storeTransaction(
            [
                'journal'                 => $journal,
                'account'                 => $accounts['destination'],
                'amount'                  => $amount,
                'transaction_currency_id' => $transaction['transaction_currency_id'],
                'foreign_amount'          => $foreignAmount,
                'foreign_currency_id'     => $transaction['foreign_currency_id'],
                'description'             => $transaction['description'],
                'category'                => null,
                'budget'                  => null,
                'identifier'              => $identifier,
            ]
        );
        JournalSupport::storeCategoryWithTransaction($two, $transaction['category']);
        JournalSupport::storeBudgetWithTransaction($two, $transaction['budget_id']);

        return new Collection([$one, $two]);
    }

    /**
     * @param TransactionJournal $journal
     * @param Account            $account
     * @param array              $data
     *
     * @throws FireflyException
     */
    private function updateDestinationTransaction(TransactionJournal $journal, Account $account, array $data)
    {
        $set = $journal->transactions()->where('amount', '>', 0)->get();
        if ($set->count() != 1) {
            throw new FireflyException(sprintf('Journal #%d has %d transactions with an amount more than zero.', $journal->id, $set->count()));
        }
        /** @var Transaction $transaction */
        $transaction                          = $set->first();
        $transaction->amount                  = app('steam')->positive($data['amount']);
        $transaction->transaction_currency_id = $data['currency_id'];
        $transaction->foreign_amount          = is_null($data['foreign_amount']) ? null : app('steam')->positive($data['foreign_amount']);
        $transaction->foreign_currency_id     = $data['foreign_currency_id'];

        $transaction->account_id = $account->id;
        $transaction->save();

    }

    /**
     * @param TransactionJournal $journal
     * @param Account            $account
     * @param array              $data
     *
     * @throws FireflyException
     */
    private function updateSourceTransaction(TransactionJournal $journal, Account $account, array $data)
    {
        // should be one:
        $set = $journal->transactions()->where('amount', '<', 0)->get();
        if ($set->count() != 1) {
            throw new FireflyException(sprintf('Journal #%d has %d transactions with an amount more than zero.', $journal->id, $set->count()));
        }
        /** @var Transaction $transaction */
        $transaction                          = $set->first();
        $transaction->amount                  = bcmul(app('steam')->positive($data['amount']), '-1');
        $transaction->transaction_currency_id = $data['currency_id'];
        $transaction->foreign_amount          = is_null($data['foreign_amount']) ? null : bcmul(app('steam')->positive($data['foreign_amount']), '-1');
        $transaction->foreign_currency_id     = $data['foreign_currency_id'];
        $transaction->save();
    }

}