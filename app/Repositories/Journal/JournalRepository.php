<?php

namespace FireflyIII\Repositories\Journal;

use Auth;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;

/**
 * Class JournalRepository
 *
 * @package FireflyIII\Repositories\Journal
 */
class JournalRepository implements JournalRepositoryInterface
{

    /**
     *
     * Get the account_id, which is the asset account that paid for the transaction.
     *
     * @param TransactionJournal $journal
     *
     * @return mixed
     */
    public function getAssetAccount(TransactionJournal $journal)
    {
        $positive = true; // the asset account is in the transaction with the positive amount.
        switch ($journal->transactionType->type) {
            case 'Withdrawal':
                $positive = false;
                break;
        }
        /** @var Transaction $transaction */
        foreach ($journal->transactions()->get() as $transaction) {
            if (floatval($transaction->amount) > 0 && $positive === true) {
                return $transaction->account_id;
            }
            if (floatval($transaction->amount) < 0 && $positive === false) {
                return $transaction->account_id;
            }

        }

        return $journal->transactions()->first()->account_id;
    }

    /**
     * @param string             $query
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public function searchRelated($query, TransactionJournal $journal)
    {
        $start = clone $journal->date;
        $end   = clone $journal->date;
        $start->startOfMonth();
        $end->endOfMonth();

        // get already related transactions:
        $exclude = [$journal->id];
        foreach ($journal->transactiongroups()->get() as $group) {
            foreach ($group->transactionjournals()->get() as $current) {
                $exclude[] = $current->id;
            }
        }
        $exclude = array_unique($exclude);

        /** @var Collection $collection */
        $collection = Auth::user()->transactionjournals()
                          ->withRelevantData()
                          ->before($end)->after($start)->where('encrypted', 0)
                          ->whereNotIn('id', $exclude)
                          ->where('description', 'LIKE', '%' . $query . '%')
                          ->get();

        // manually search encrypted entries:
        /** @var Collection $encryptedCollection */
        $encryptedCollection = Auth::user()->transactionjournals()
                                   ->withRelevantData()
                                   ->before($end)->after($start)
                                   ->where('encrypted', 1)
                                   ->whereNotIn('id', $exclude)
                                   ->get();
        $encrypted           = $encryptedCollection->filter(
            function (TransactionJournal $journal) use ($query) {
                $strPos = strpos(strtolower($journal->description), strtolower($query));
                if ($strPos !== false) {
                    return $journal;
                }

                return null;
            }
        );

        return $collection->merge($encrypted);
    }

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
        $journal->completed = 1;
        $journal->save();

        return $journal;


    }


    /**
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return mixed
     */
    public function update(TransactionJournal $journal, array $data)
    {
        // update actual journal.
        $journal->transaction_currency_id = $data['amount_currency_id'];
        $journal->description             = $data['description'];
        $journal->date                    = $data['date'];


        // unlink all categories, recreate them:
        $journal->categories()->detach();
        if (strlen($data['category']) > 0) {
            $category = Category::firstOrCreate(['name' => $data['category'], 'user_id' => $data['user']]);
            $journal->categories()->save($category);
        }

        // unlink all budgets and recreate them:
        $journal->budgets()->detach();
        if (intval($data['budget_id']) > 0) {
            $budget = Budget::find($data['budget_id']);
            $journal->budgets()->save($budget);
        }

        // store accounts (depends on type)
        switch ($journal->transactionType->type) {
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

        // update the from and to transaction.
        /** @var Transaction $transaction */
        foreach ($journal->transactions()->get() as $transaction) {
            if (floatval($transaction->amount) < 0) {
                // this is the from transaction, negative amount:
                $transaction->amount     = $data['amount'] * -1;
                $transaction->account_id = $from->id;
                $transaction->save();
            }
            if (floatval($transaction->amount) > 0) {
                $transaction->amount     = $data['amount'];
                $transaction->account_id = $to->id;
                $transaction->save();
            }
        }


        $journal->save();

        return $journal;
    }

}