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

declare(strict_types = 1);

namespace FireflyIII\Repositories\Journal;

use DB;
use FireflyIII\Events\TransactionStored;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class JournalRepository
 *
 * @package FireflyIII\Repositories\Journal
 */
class JournalRepository implements JournalRepositoryInterface
{
    /** @var User */
    private $user;

    /** @var array */
    private $validMetaFields = ['interest_date', 'book_date', 'process_date', 'due_date', 'payment_date', 'invoice_date', 'internal_reference', 'notes'];

    /**
     * JournalRepository constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
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
    public function find(int $journalId) : TransactionJournal
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
     * @param array $data
     *
     * @return TransactionJournal
     */
    public function store(array $data): TransactionJournal
    {
        // find transaction type.
        $transactionType = TransactionType::where('type', ucfirst($data['what']))->first();
        $journal         = new TransactionJournal(
            [
                'user_id'                 => $data['user'],
                'transaction_type_id'     => $transactionType->id,
                'transaction_currency_id' => $data['currency_id'],
                'description'             => $data['description'],
                'completed'               => 0,
                'date'                    => $data['date'],
            ]
        );
        $journal->save();

        // store stuff:
        $this->storeCategoryWithJournal($journal, $data['category']);
        $this->storeBudgetWithJournal($journal, $data['budget_id']);
        $accounts = $this->storeAccounts($transactionType, $data);

        // store two transactions:
        $one = [
            'journal'     => $journal,
            'account'     => $accounts['source'],
            'amount'      => bcmul(strval($data['amount']), '-1'),
            'description' => null,
            'category'    => null,
            'budget'      => null,
            'identifier'  => 0,
        ];
        $this->storeTransaction($one);

        $two = [
            'journal'     => $journal,
            'account'     => $accounts['destination'],
            'amount'      => $data['amount'],
            'description' => null,
            'category'    => null,
            'budget'      => null,
            'identifier'  => 0,
        ];

        $this->storeTransaction($two);


        // store tags
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->saveTags($journal, $data['tags']);
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
     * Store journal only, uncompleted, with attachments if necessary.
     *
     * @param array $data
     *
     * @return TransactionJournal
     */
    public function storeJournal(array $data): TransactionJournal
    {
        // find transaction type.
        $transactionType = TransactionType::where('type', ucfirst($data['what']))->first();

        // store actual journal.
        $journal = new TransactionJournal(
            [
                'user_id'                 => $data['user'],
                'transaction_type_id'     => $transactionType->id,
                'transaction_currency_id' => $data['amount_currency_id_amount'],
                'description'             => $data['description'],
                'completed'               => 0,
                'date'                    => $data['date'],
            ]
        );

        $result = $journal->save();
        if ($result) {
            return $journal;
        }

        return new TransactionJournal();


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
        $journal->transaction_currency_id = $data['currency_id'];
        $journal->description             = $data['description'];
        $journal->date                    = $data['date'];

        // unlink all categories, recreate them:
        $journal->categories()->detach();
        $journal->budgets()->detach();

        $this->storeCategoryWithJournal($journal, $data['category']);
        $this->storeBudgetWithJournal($journal, $data['budget_id']);
        $accounts = $this->storeAccounts($journal->transactionType, $data);

        $sourceAmount = bcmul(strval($data['amount']), '-1');
        $this->updateSourceTransaction($journal, $accounts['source'], $sourceAmount); // negative because source loses money.

        $amount = strval($data['amount']);
        $this->updateDestinationTransaction($journal, $accounts['destination'], $amount); // positive because destination gets money.

        $journal->save();

        // update tags:
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->updateTags($journal, $data['tags']);
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
        $journal->transaction_currency_id = $data['currency_id'];
        $journal->description             = $data['journal_description'];
        $journal->date                    = $data['date'];
        $journal->save();
        
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

            return $journal;
        }


        // update tags:
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->updateTags($journal, $data['tags']);
        }

        // delete original transactions, and recreate them.
        $journal->transactions()->delete();

        // store each transaction.
        $identifier = 0;
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
     *
     * * Remember: a balancingAct takes at most one expense and one transfer.
     *            an advancePayment takes at most one expense, infinite deposits and NO transfers.
     *
     * @param TransactionJournal $journal
     * @param array              $array
     *
     * @return bool
     */
    private function saveTags(TransactionJournal $journal, array $array): bool
    {
        /** @var TagRepositoryInterface $tagRepository */
        $tagRepository = app(TagRepositoryInterface::class);

        foreach ($array as $name) {
            if (strlen(trim($name)) > 0) {
                $tag = Tag::firstOrCreateEncrypted(['tag' => $name, 'user_id' => $journal->user_id]);
                if (!is_null($tag)) {
                    $tagRepository->connect($journal, $tag);
                }
            }
        }

        return true;
    }

    /**
     * @param TransactionType $type
     * @param array           $data
     *
     * @return array
     * @throws FireflyException
     */
    private function storeAccounts(TransactionType $type, array $data): array
    {
        $accounts = [
            'source'      => null,
            'destination' => null,
        ];

        Log::debug(sprintf('Going to store accounts for type %s', $type->type));
        switch ($type->type) {
            case TransactionType::WITHDRAWAL:
                $accounts = $this->storeWithdrawalAccounts($data);
                break;

            case TransactionType::DEPOSIT:
                $accounts = $this->storeDepositAccounts($data);

                break;
            case TransactionType::TRANSFER:
                $accounts['source']      = Account::where('user_id', $this->user->id)->where('id', $data['source_account_id'])->first();
                $accounts['destination'] = Account::where('user_id', $this->user->id)->where('id', $data['destination_account_id'])->first();
                break;
            default:
                throw new FireflyException(sprintf('Did not recognise transaction type "%s".', $type->type));
        }

        if (is_null($accounts['source'])) {
            Log::error('"source"-account is null, so we cannot continue!', ['data' => $data]);
            throw new FireflyException('"source"-account is null, so we cannot continue!');
        }

        if (is_null($accounts['destination'])) {
            Log::error('"destination"-account is null, so we cannot continue!', ['data' => $data]);
            throw new FireflyException('"destination"-account is null, so we cannot continue!');

        }


        return $accounts;
    }

    /**
     * @param TransactionJournal $journal
     * @param int                $budgetId
     */
    private function storeBudgetWithJournal(TransactionJournal $journal, int $budgetId)
    {
        if (intval($budgetId) > 0 && $journal->transactionType->type !== TransactionType::TRANSFER) {
            /** @var \FireflyIII\Models\Budget $budget */
            $budget = Budget::find($budgetId);
            $journal->budgets()->save($budget);
        }
    }

    /**
     * @param Transaction $transaction
     * @param int         $budgetId
     */
    private function storeBudgetWithTransaction(Transaction $transaction, int $budgetId)
    {
        if (intval($budgetId) > 0 && $transaction->transactionJournal->transactionType->type !== TransactionType::TRANSFER) {
            /** @var \FireflyIII\Models\Budget $budget */
            $budget = Budget::find($budgetId);
            $transaction->budgets()->save($budget);
        }
    }

    /**
     * @param TransactionJournal $journal
     * @param string             $category
     */
    private function storeCategoryWithJournal(TransactionJournal $journal, string $category)
    {
        if (strlen($category) > 0) {
            $category = Category::firstOrCreateEncrypted(['name' => $category, 'user_id' => $journal->user_id]);
            $journal->categories()->save($category);
        }
    }

    /**
     * @param Transaction $transaction
     * @param string      $category
     */
    private function storeCategoryWithTransaction(Transaction $transaction, string $category)
    {
        if (strlen($category) > 0) {
            $category = Category::firstOrCreateEncrypted(['name' => $category, 'user_id' => $transaction->transactionJournal->user_id]);
            $transaction->categories()->save($category);
        }
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function storeDepositAccounts(array $data): array
    {
        $destinationAccount = Account::where('user_id', $this->user->id)->where('id', $data['destination_account_id'])->first(['accounts.*']);

        if (strlen($data['source_account_name']) > 0) {
            $sourceType    = AccountType::where('type', 'Revenue account')->first();
            $sourceAccount = Account::firstOrCreateEncrypted(
                ['user_id' => $data['user'], 'account_type_id' => $sourceType->id, 'name' => $data['source_account_name'], 'active' => 1]
            );

            return [
                'source'      => $sourceAccount,
                'destination' => $destinationAccount,
            ];
        }
        $sourceType    = AccountType::where('type', 'Cash account')->first();
        $sourceAccount = Account::firstOrCreateEncrypted(
            ['user_id' => $data['user'], 'account_type_id' => $sourceType->id, 'name' => 'Cash account', 'active' => 1]
        );

        return [
            'source'      => $sourceAccount,
            'destination' => $destinationAccount,
        ];
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
        $accounts = $this->storeAccounts($journal->transactionType, $transaction);

        // store transaction one way:
        $one = $this->storeTransaction(
            [
                'journal'     => $journal,
                'account'     => $accounts['source'],
                'amount'      => bcmul(strval($transaction['amount']), '-1'),
                'description' => $transaction['description'],
                'category'    => null,
                'budget'      => null,
                'identifier'  => $identifier,
            ]
        );
        $this->storeCategoryWithTransaction($one, $transaction['category']);
        $this->storeBudgetWithTransaction($one, $transaction['budget_id']);

        // and the other way:
        $two = $this->storeTransaction(
            [
                'journal'     => $journal,
                'account'     => $accounts['destination'],
                'amount'      => strval($transaction['amount']),
                'description' => $transaction['description'],
                'category'    => null,
                'budget'      => null,
                'identifier'  => $identifier,
            ]
        );
        $this->storeCategoryWithTransaction($two, $transaction['category']);
        $this->storeBudgetWithTransaction($two, $transaction['budget_id']);

        return new Collection([$one, $two]);
    }

    /**
     * @param array $data
     *
     * @return Transaction
     */
    private function storeTransaction(array $data): Transaction
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::create(
            [
                'transaction_journal_id' => $data['journal']->id,
                'account_id'             => $data['account']->id,
                'amount'                 => $data['amount'],
                'description'            => $data['description'],
                'identifier'             => $data['identifier'],
            ]
        );

        Log::debug(sprintf('Transaction stored with ID: %s', $transaction->id));

        if (!is_null($data['category'])) {
            $transaction->categories()->save($data['category']);
        }

        if (!is_null($data['budget'])) {
            $transaction->categories()->save($data['budget']);
        }

        return $transaction;

    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function storeWithdrawalAccounts(array $data): array
    {
        $sourceAccount = Account::where('user_id', $this->user->id)->where('id', $data['source_account_id'])->first(['accounts.*']);

        if (strlen($data['destination_account_name']) > 0) {
            $destinationType    = AccountType::where('type', AccountType::EXPENSE)->first();
            $destinationAccount = Account::firstOrCreateEncrypted(
                [
                    'user_id'         => $data['user'],
                    'account_type_id' => $destinationType->id,
                    'name'            => $data['destination_account_name'],
                    'active'          => 1,
                ]
            );

            return [
                'source'      => $sourceAccount,
                'destination' => $destinationAccount,
            ];
        }
        $destinationType    = AccountType::where('type', 'Cash account')->first();
        $destinationAccount = Account::firstOrCreateEncrypted(
            ['user_id' => $data['user'], 'account_type_id' => $destinationType->id, 'name' => 'Cash account', 'active' => 1]
        );

        return [
            'source'      => $sourceAccount,
            'destination' => $destinationAccount,
        ];


    }

    /**
     * @param TransactionJournal $journal
     * @param Account            $account
     * @param string             $amount
     *
     * @throws FireflyException
     */
    private function updateDestinationTransaction(TransactionJournal $journal, Account $account, string $amount)
    {
        // should be one:
        $set = $journal->transactions()->where('amount', '>', 0)->get();
        if ($set->count() != 1) {
            throw new FireflyException(
                sprintf('Journal #%d has an unexpected (%d) amount of transactions with an amount more than zero.', $journal->id, $set->count())
            );
        }
        /** @var Transaction $transaction */
        $transaction             = $set->first();
        $transaction->amount     = $amount;
        $transaction->account_id = $account->id;
        $transaction->save();

    }

    /**
     * @param TransactionJournal $journal
     * @param Account            $account
     * @param string             $amount
     *
     * @throws FireflyException
     */
    private function updateSourceTransaction(TransactionJournal $journal, Account $account, string $amount)
    {
        // should be one:
        $set = $journal->transactions()->where('amount', '<', 0)->get();
        if ($set->count() != 1) {
            throw new FireflyException(
                sprintf('Journal #%d has an unexpected (%d) amount of transactions with an amount less than zero.', $journal->id, $set->count())
            );
        }
        /** @var Transaction $transaction */
        $transaction             = $set->first();
        $transaction->amount     = $amount;
        $transaction->account_id = $account->id;
        $transaction->save();


    }

    /**
     * @param TransactionJournal $journal
     * @param array              $array
     *
     * @return bool
     */
    private function updateTags(TransactionJournal $journal, array $array): bool
    {
        // create tag repository
        /** @var TagRepositoryInterface $tagRepository */
        $tagRepository = app(TagRepositoryInterface::class);


        // find or create all tags:
        $tags = [];
        $ids  = [];
        foreach ($array as $name) {
            if (strlen(trim($name)) > 0) {
                $tag    = Tag::firstOrCreateEncrypted(['tag' => $name, 'user_id' => $journal->user_id]);
                $tags[] = $tag;
                $ids[]  = $tag->id;
            }
        }

        // delete all tags connected to journal not in this array:
        if (count($ids) > 0) {
            DB::table('tag_transaction_journal')->where('transaction_journal_id', $journal->id)->whereNotIn('tag_id', $ids)->delete();
        }
        // if count is zero, delete them all:
        if (count($ids) == 0) {
            DB::table('tag_transaction_journal')->where('transaction_journal_id', $journal->id)->delete();
        }

        // connect each tag to journal (if not yet connected):
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $tagRepository->connect($journal, $tag);
        }

        return true;
    }
}
