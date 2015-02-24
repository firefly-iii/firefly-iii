<?php

namespace FireflyIII\Repositories\Journal;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;

/**
 * Class JournalRepository
 *
 * @package FireflyIII\Repositories\Journal
 */
class JournalRepository implements JournalRepositoryInterface
{

    /**
     * @param array $data
     *
     * @return TransactionJournal
     */
    public function store(array $data)
    {
        // find transaction type.
        $transactionType = TransactionType::where('type', ucfirst($data['what']))->first();

        // store actual journal.
        $journal = new TransactionJournal(
            [
                'user_id'                 => $data['user'],
                'transaction_type_id'     => $transactionType->id,
                'transaction_currency_id' => $data['amount_currency_id'],
                'description'             => $data['description'],
                'completed'               => 0,
                'date'                    => $data['date'],
            ]
        );
        $journal->save();


        // store or get category
        if (strlen($data['category']) > 0) {
            $category = Category::firstOrCreate(['name' => $data['category'], 'user_id' => $data['user']]);
            $journal->categories()->save($category);
        }

        // store or get budget
        if (intval($data['budget_id']) > 0) {
            $budget = Budget::find($data['budget_id']);
            $journal->budgets()->save($budget);
        }

        // store accounts (depends on type)
        switch ($transactionType->type) {
            case 'Withdrawal':

                $from = Account::find($data['account_id']);

                if (strlen($data['expense_account']) > 0) {
                    $toType = AccountType::where('type', 'Expense account')->first();
                    $to     = Account::firstOrCreate(
                        ['user_id' => $data['user'], 'account_type_id' => $toType->id, 'name' => $data['expense_account'], 'active' => 1]
                    );
                } else {
                    $toType = AccountType::where('type', 'Cash account')->first();
                    $to     = Account::firstOrCreate(['user_id' => $data['user'], 'account_type_id' => $toType->id, 'name' => 'Cash account', 'active' => 1]);
                }
                break;

            case 'Deposit':
                $to = Account::find($data['account_id']);

                if (strlen($data['revenue_account']) > 0) {
                    $fromType = AccountType::where('type', 'Revenue account')->first();
                    $from     = Account::firstOrCreate(
                        ['user_id' => $data['user'], 'account_type_id' => $fromType->id, 'name' => $data['revenue_account'], 'active' => 1]
                    );
                } else {
                    $toType = AccountType::where('type', 'Cash account')->first();
                    $from   = Account::firstOrCreate(['user_id' => $data['user'], 'account_type_id' => $toType->id, 'name' => 'Cash account', 'active' => 1]);
                }

                break;
            case 'Transfer':
                $from = Account::find($data['account_from_id']);
                $to   = Account::find($data['account_to_id']);
                break;
        }

        // store accompanying transactions.
        Transaction::create( // first transaction.
            [
                'account_id'             => $from->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $data['amount'] * -1
            ]
        );
        Transaction::create( // second transaction.
            [
                'account_id'             => $to->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $data['amount']
            ]
        );

        return $journal;


    }
}