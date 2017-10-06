<?php
/**
 * JournalRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\Journal;

use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Log;
use Preferences;

/**
 * Class JournalRepository
 *
 * @package FireflyIII\Repositories\Journal
 */
class JournalRepository implements JournalRepositoryInterface
{
    use CreateJournalsTrait, UpdateJournalsTrait, SupportJournalsTrait;

    /** @var User */
    private $user;
    /** @var array */
    private $validMetaFields = ['interest_date', 'book_date', 'process_date', 'due_date', 'payment_date', 'invoice_date', 'internal_reference'];

    /**
     * @param TransactionJournal $journal
     * @param TransactionType    $type
     * @param Account            $source
     * @param Account            $destination
     *
     * @return MessageBag
     */
    public function convert(TransactionJournal $journal, TransactionType $type, Account $source, Account $destination): MessageBag
    {
        // default message bag that shows errors for everything.
        $messages = new MessageBag;
        $messages->add('source_account_revenue', trans('firefly.invalid_convert_selection'));
        $messages->add('destination_account_asset', trans('firefly.invalid_convert_selection'));
        $messages->add('destination_account_expense', trans('firefly.invalid_convert_selection'));
        $messages->add('source_account_asset', trans('firefly.invalid_convert_selection'));

        if ($source->id === $destination->id || is_null($source->id) || is_null($destination->id)) {
            return $messages;
        }

        $sourceTransaction             = $journal->transactions()->where('amount', '<', 0)->first();
        $destinationTransaction        = $journal->transactions()->where('amount', '>', 0)->first();
        $sourceTransaction->account_id = $source->id;
        $sourceTransaction->save();
        $destinationTransaction->account_id = $destination->id;
        $destinationTransaction->save();
        $journal->transaction_type_id = $type->id;
        $journal->save();

        // if journal is a transfer now, remove budget:
        if ($type->type === TransactionType::TRANSFER) {
            $journal->budgets()->detach();
        }

        Preferences::mark();

        return new MessageBag;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return int
     */
    public function countTransactions(TransactionJournal $journal): int
    {
        return $journal->transactions()->count();
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function delete(TransactionJournal $journal): bool
    {
        $journal->delete();

        return true;
    }

    /**
     * @param int $journalId
     *
     * @return TransactionJournal
     */
    public function find(int $journalId): TransactionJournal
    {
        $journal = $this->user->transactionJournals()->where('id', $journalId)->first();
        if (is_null($journal)) {
            return new TransactionJournal;
        }

        return $journal;
    }

    /**
     * Get users first transaction journal
     *
     * @return TransactionJournal
     */
    public function first(): TransactionJournal
    {
        $entry = $this->user->transactionJournals()->orderBy('date', 'ASC')->first(['transaction_journals.*']);

        if (is_null($entry)) {

            return new TransactionJournal;
        }

        return $entry;
    }

    /**
     * @return Collection
     */
    public function getTransactionTypes(): Collection
    {
        return TransactionType::orderBy('type', 'ASC')->get();
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function isTransfer(TransactionJournal $journal): bool
    {
        return $journal->transactionType->type === TransactionType::TRANSFER;
    }

    /**
     * @param TransactionJournal $journal
     * @param int                $order
     *
     * @return bool
     */
    public function setOrder(TransactionJournal $journal, int $order): bool
    {
        $journal->order = $order;
        $journal->save();

        return true;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return TransactionJournal
     */
    public function store(array $data): TransactionJournal
    {
        // find transaction type.
        /** @var TransactionType $transactionType */
        $transactionType = TransactionType::where('type', ucfirst($data['what']))->first();
        $accounts        = $this->storeAccounts($this->user, $transactionType, $data);
        $data            = $this->verifyNativeAmount($data, $accounts);
        $amount          = strval($data['amount']);
        $journal         = new TransactionJournal(
            [
                'user_id'                 => $this->user->id,
                'transaction_type_id'     => $transactionType->id,
                'transaction_currency_id' => $data['currency_id'], // no longer used.
                'description'             => $data['description'],
                'completed'               => 0,
                'date'                    => $data['date'],
            ]
        );
        $journal->save();

        // store stuff:
        $this->storeCategoryWithJournal($journal, strval($data['category']));
        $this->storeBudgetWithJournal($journal, $data['budget_id']);

        // store two transactions:
        $one = [
            'journal'                 => $journal,
            'account'                 => $accounts['source'],
            'amount'                  => bcmul($amount, '-1'),
            'transaction_currency_id' => $data['currency_id'],
            'foreign_amount'          => is_null($data['foreign_amount']) ? null : bcmul(strval($data['foreign_amount']), '-1'),
            'foreign_currency_id'     => $data['foreign_currency_id'],
            'description'             => null,
            'category'                => null,
            'budget'                  => null,
            'identifier'              => 0,
        ];
        $this->storeTransaction($one);

        $two = [
            'journal'                 => $journal,
            'account'                 => $accounts['destination'],
            'amount'                  => $amount,
            'transaction_currency_id' => $data['currency_id'],
            'foreign_amount'          => $data['foreign_amount'],
            'foreign_currency_id'     => $data['foreign_currency_id'],
            'description'             => null,
            'category'                => null,
            'budget'                  => null,
            'identifier'              => 0,
        ];

        $this->storeTransaction($two);


        // store tags
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->saveTags($journal, $data['tags']);
        }

        // update note:
        if (isset($data['notes'])) {
            $this->updateNote($journal, $data['notes']);
        }

        foreach ($data as $key => $value) {
            if (in_array($key, $this->validMetaFields)) {
                $journal->setMeta($key, $value);
                continue;
            }
            Log::debug(sprintf('Could not store meta field "%s" with value "%s" for journal #%d', json_encode($key), json_encode($value), $journal->id));
        }

        $journal->completed = 1;
        $journal->save();

        return $journal;

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
        $accounts               = $this->storeAccounts($this->user, $journal->transactionType, $data);
        $data                   = $this->verifyNativeAmount($data, $accounts);
        $data['amount']         = strval($data['amount']);
        $data['foreign_amount'] = is_null($data['foreign_amount']) ? null : strval($data['foreign_amount']);

        // unlink all categories, recreate them:
        $journal->categories()->detach();
        $journal->budgets()->detach();

        $this->storeCategoryWithJournal($journal, strval($data['category']));
        $this->storeBudgetWithJournal($journal, $data['budget_id']);

        // negative because source loses money.
        $this->updateSourceTransaction($journal, $accounts['source'], $data);

        // positive because destination gets money.
        $this->updateDestinationTransaction($journal, $accounts['destination'], $data);

        $journal->save();

        // update tags:
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->updateTags($journal, $data['tags']);
        }

        // update note:
        if (isset($data['notes'])) {
            $this->updateNote($journal, $data['notes']);
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

        // update note:
        $this->updateNote($journal, $data['notes']);

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
            $this->updateTags($journal, $data['tags']);
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
}
