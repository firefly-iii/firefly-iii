<?php
/**
 * Journal.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Crud\Split;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class Journal
 *
 * @package FireflyIII\Crud\Split
 */
class Journal implements JournalInterface
{
    /** @var User */
    private $user;

    /**
     * AttachmentRepository constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return TransactionJournal
     */
    public function storeJournal(array $data) : TransactionJournal
    {
        // find transaction type.
        $transactionType = TransactionType::where('type', ucfirst($data['what']))->first();
        $journal         = new TransactionJournal(
            [
                'user_id'                 => $this->user->id,
                'transaction_type_id'     => $transactionType->id,
                'transaction_currency_id' => $data['journal_currency_id'],
                'description'             => $data['journal_description'],
                'completed'               => 0,
                'date'                    => $data['date'],
                'interest_date'           => $data['interest_date'],
                'book_date'               => $data['book_date'],
                'process_date'            => $data['process_date'],
            ]
        );
        $journal->save();

        foreach ($data['transactions'] as $transaction) {
            $this->storeTransaction($journal, $transaction);
        }

        $journal->completed = true;
        $journal->save();

        return $journal;
    }

    /**
     * @param TransactionJournal $journal
     * @param array              $transaction
     *
     * @return Collection
     */
    public function storeTransaction(TransactionJournal $journal, array $transaction): Collection
    {
        // store accounts (depends on type)
        list($sourceAccount, $destinationAccount) = $this->storeAccounts($journal->transactionType->type, $transaction);

        // store transaction one way:
        /** @var Transaction $one */
        $one = Transaction::create( // first transaction.
            [
                'account_id'             => $sourceAccount->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $transaction['amount'] * -1,
                'description'            => $transaction['description'],
            ]
        );

        // store transaction the other way:
        $two = Transaction::create( // first transaction.
            [
                'account_id'             => $destinationAccount->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $transaction['amount'],
                'description'            => $transaction['description'],
            ]
        );

        // store or get category and connect:
        if (strlen($transaction['category']) > 0) {
            $category = Category::firstOrCreateEncrypted(['name' => $transaction['category'], 'user_id' => $journal->user_id]);
            $one->categories()->save($category);
            $two->categories()->save($category);
        }
        // store or get budget
        if (intval($transaction['budget_id']) > 0) {
            $budget = Budget::find($transaction['budget_id']);
            $one->budgets()->save($budget);
            $two->budgets()->save($budget);
        }

        return new Collection([$one, $two]);

    }

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     */
    public function updateJournal(TransactionJournal $journal, array $data): TransactionJournal
    {
        echo '<pre>';
        print_r($data);

        $journal->description             = $data['journal_description'];
        $journal->transaction_currency_id = $data['journal_currency_id'];
        $journal->date                    = $data['date'];
        $journal->interest_date           = $data['interest_date'];
        $journal->book_date               = $data['book_date'];
        $journal->process_date            = $data['process_date'];
        $journal->save();

        // delete original transactions, and recreate them.
        $journal->transactions()->delete();

        foreach ($data['transactions'] as $transaction) {
            $this->storeTransaction($journal, $transaction);
        }

        $journal->completed = true;
        $journal->save();

        return $journal;
    }

    /**
     * @param string $type
     * @param array  $transaction
     *
     * @return array
     * @throws FireflyException
     */
    private function storeAccounts(string $type, array $transaction): array
    {
        $sourceAccount      = null;
        $destinationAccount = null;
        switch ($type) {
            case TransactionType::WITHDRAWAL:
                list($sourceAccount, $destinationAccount) = $this->storeWithdrawalAccounts($transaction);
                break;
            case TransactionType::DEPOSIT:
                list($sourceAccount, $destinationAccount) = $this->storeDepositAccounts($transaction);
                break;
            case TransactionType::TRANSFER:
                $sourceAccount      = Account::where('user_id', $this->user->id)->where('id', $transaction['source_account_id'])->first();
                $destinationAccount = Account::where('user_id', $this->user->id)->where('id', $transaction['destination_account_id'])->first();
                break;
            default:
                throw new FireflyException('Cannot handle ' . e($type));
        }

        return [$sourceAccount, $destinationAccount];
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
            $fromType    = AccountType::where('type', 'Revenue account')->first();
            $fromAccount = Account::firstOrCreateEncrypted(
                ['user_id' => $this->user->id, 'account_type_id' => $fromType->id, 'name' => $data['source_account_name'], 'active' => 1]
            );

            return [$fromAccount, $destinationAccount];
        } else {
            $fromType    = AccountType::where('type', 'Cash account')->first();
            $fromAccount = Account::firstOrCreateEncrypted(
                ['user_id' => $this->user->id, 'account_type_id' => $fromType->id, 'name' => 'Cash account', 'active' => 1]
            );
        }

        return [$fromAccount, $destinationAccount];
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
            $destinationType    = AccountType::where('type', 'Expense account')->first();
            $destinationAccount = Account::firstOrCreateEncrypted(
                [
                    'user_id'         => $this->user->id,
                    'account_type_id' => $destinationType->id,
                    'name'            => $data['destination_account_name'],
                    'active'          => 1,
                ]
            );

            return [$sourceAccount, $destinationAccount];
        }
        $destinationType    = AccountType::where('type', 'Cash account')->first();
        $destinationAccount = Account::firstOrCreateEncrypted(
            ['user_id' => $this->user->id, 'account_type_id' => $destinationType->id, 'name' => 'Cash account', 'active' => 1]
        );

        return [$sourceAccount, $destinationAccount];


    }
}