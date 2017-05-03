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

use DB;
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
    /** @var User */
    private $user;

    /** @var array */
    private $validMetaFields
        = ['interest_date', 'book_date', 'process_date', 'due_date', 'payment_date', 'invoice_date', 'internal_reference', 'notes', 'foreign_amount',
           'foreign_currency_id',
        ];

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
        $accounts        = $this->storeAccounts($transactionType, $data);
        $data            = $this->verifyNativeAmount($data, $accounts);
        $currencyId      = $data['currency_id'];
        $amount          = strval($data['amount']);
        $journal         = new TransactionJournal(
            [
                'user_id'                 => $this->user->id,
                'transaction_type_id'     => $transactionType->id,
                'transaction_currency_id' => $currencyId,
                'description'             => $data['description'],
                'completed'               => 0,
                'date'                    => $data['date'],
            ]
        );
        $journal->save();

        // store stuff:
        $this->storeCategoryWithJournal($journal, $data['category']);
        $this->storeBudgetWithJournal($journal, $data['budget_id']);


        // store two transactions:
        $one = [
            'journal'     => $journal,
            'account'     => $accounts['source'],
            'amount'      => bcmul($amount, '-1'),
            'description' => null,
            'category'    => null,
            'budget'      => null,
            'identifier'  => 0,
        ];
        $this->storeTransaction($one);

        $two = [
            'journal'     => $journal,
            'account'     => $accounts['destination'],
            'amount'      => $amount,
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
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     */
    public function update(TransactionJournal $journal, array $data): TransactionJournal
    {

        // update actual journal:
        $journal->description = $data['description'];
        $journal->date        = $data['date'];
        $accounts             = $this->storeAccounts($journal->transactionType, $data);
        $amount               = strval($data['amount']);

        if ($data['currency_id'] !== $journal->transaction_currency_id) {
            // user has entered amount in foreign currency.
            // amount in "our" currency is $data['exchanged_amount']:
            $amount = strval($data['exchanged_amount']);
            // other values must be stored as well:
            $data['original_amount']      = $data['amount'];
            $data['original_currency_id'] = $data['currency_id'];

        }

        // unlink all categories, recreate them:
        $journal->categories()->detach();
        $journal->budgets()->detach();

        $this->storeCategoryWithJournal($journal, $data['category']);
        $this->storeBudgetWithJournal($journal, $data['budget_id']);


        $this->updateSourceTransaction($journal, $accounts['source'], bcmul($amount, '-1')); // negative because source loses money.
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
                    Log::debug(sprintf('Will try to connect tag #%d to journal #%d.', $tag->id, $journal->id));
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
        if (intval($budgetId) > 0 && $journal->transactionType->type === TransactionType::WITHDRAWAL) {
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
        Log::debug('Now in storeDepositAccounts().');
        $destinationAccount = Account::where('user_id', $this->user->id)->where('id', $data['destination_account_id'])->first(['accounts.*']);

        Log::debug(sprintf('Destination account is #%d ("%s")', $destinationAccount->id, $destinationAccount->name));

        if (strlen($data['source_account_name']) > 0) {
            $sourceType    = AccountType::where('type', 'Revenue account')->first();
            $sourceAccount = Account::firstOrCreateEncrypted(
                ['user_id' => $this->user->id, 'account_type_id' => $sourceType->id, 'name' => $data['source_account_name'], 'active' => 1]
            );

            Log::debug(sprintf('source account name is "%s", account is %d', $data['source_account_name'], $sourceAccount->id));

            return [
                'source'      => $sourceAccount,
                'destination' => $destinationAccount,
            ];
        }

        Log::debug('source_account_name is empty, so default to cash account!');

        $sourceType    = AccountType::where('type', AccountType::CASH)->first();
        $sourceAccount = Account::firstOrCreateEncrypted(
            ['user_id' => $this->user->id, 'account_type_id' => $sourceType->id, 'name' => 'Cash account', 'active' => 1]
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
        Log::debug('Now in storeWithdrawalAccounts().');
        $sourceAccount = Account::where('user_id', $this->user->id)->where('id', $data['source_account_id'])->first(['accounts.*']);

        Log::debug(sprintf('Source account is #%d ("%s")', $sourceAccount->id, $sourceAccount->name));

        if (strlen($data['destination_account_name']) > 0) {
            $destinationType    = AccountType::where('type', AccountType::EXPENSE)->first();
            $destinationAccount = Account::firstOrCreateEncrypted(
                [
                    'user_id'         => $this->user->id,
                    'account_type_id' => $destinationType->id,
                    'name'            => $data['destination_account_name'],
                    'active'          => 1,
                ]
            );

            Log::debug(sprintf('destination account name is "%s", account is %d', $data['destination_account_name'], $destinationAccount->id));

            return [
                'source'      => $sourceAccount,
                'destination' => $destinationAccount,
            ];
        }
        Log::debug('destination_account_name is empty, so default to cash account!');
        $destinationType    = AccountType::where('type', AccountType::CASH)->first();
        $destinationAccount = Account::firstOrCreateEncrypted(
            ['user_id' => $this->user->id, 'account_type_id' => $destinationType->id, 'name' => 'Cash account', 'active' => 1]
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
            Log::debug(sprintf('Will try to connect tag #%d to journal #%d.', $tag->id, $journal->id));
            $tagRepository->connect($journal, $tag);
        }

        return true;
    }

    /**
     * This method checks the data array and the given accounts to verify that the native amount, currency
     * and possible the foreign currency and amount are properly saved.
     *
     * @param array $data
     * @param array $accounts
     *
     * @return array
     * @throws FireflyException
     */
    private function verifyNativeAmount(array $data, array $accounts): array
    {
        /** @var TransactionType $transactionType */
        $transactionType     = TransactionType::where('type', ucfirst($data['what']))->first();
        $submittedCurrencyId = $data['currency_id'];

        // which account to check for what the native currency is?
        $check = 'source';
        if ($transactionType->type === TransactionType::DEPOSIT) {
            $check = 'destination';
        }
        switch ($transactionType->type) {
            case TransactionType::DEPOSIT:
            case TransactionType::WITHDRAWAL:
                // continue:
                $nativeCurrencyId = intval($accounts[$check]->getMeta('currency_id'));

                // does not match? Then user has submitted amount in a foreign currency:
                if ($nativeCurrencyId !== $submittedCurrencyId) {
                    // store amount and submitted currency in "foreign currency" fields:
                    $data['foreign_amount']      = $data['amount'];
                    $data['foreign_currency_id'] = $submittedCurrencyId;

                    // overrule the amount and currency ID fields to be the original again:
                    $data['amount']      = strval($data['native_amount']);
                    $data['currency_id'] = $nativeCurrencyId;
                }
                break;
            case TransactionType::TRANSFER:
                // source gets the original amount.
                $data['amount']              = strval($data['source_amount']);
                $data['currency_id']         = intval($accounts['source']->getMeta('currency_id'));
                $data['foreign_amount']      = strval($data['destination_amount']);
                $data['foreign_currency_id'] = intval($accounts['destination']->getMeta('currency_id'));
                break;
            default:
                throw new FireflyException(sprintf('Cannot handle %s in verifyNativeAmount()', $transactionType->type));
        }

        return $data;
    }
}
