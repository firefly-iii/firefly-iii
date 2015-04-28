<?php

namespace FireflyIII\Repositories\Journal;

use App;
use Auth;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;
use Log;

/**
 * Class JournalRepository
 *
 * @package FireflyIII\Repositories\Journal
 */
class JournalRepository implements JournalRepositoryInterface
{

    /**
     * Get users first transaction journal
     *
     * @return TransactionJournal
     */
    public function first()
    {
        return Auth::user()->transactionjournals()->orderBy('date', 'ASC')->first(['transaction_journals.*']);
    }

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
     * @param TransactionType $dbType
     *
     * @return Collection
     */
    public function getJournalsOfType(TransactionType $dbType)
    {
        return Auth::user()->transactionjournals()->where('transaction_type_id', $dbType->id)->orderBy('id', 'DESC')->take(50)->get();
    }

    /**
     * @param $type
     *
     * @return TransactionType
     */
    public function getTransactionType($type)
    {
        return TransactionType::whereType($type)->first();
    }

    /**
     *
     * * Remember: a balancingAct takes at most one expense and one transfer.
     *            an advancePayment takes at most one expense, infinite deposits and NO transfers.
     *
     * @param TransactionJournal $journal
     * @param array              $array
     *
     * @return void
     */
    public function saveTags(TransactionJournal $journal, array $array)
    {
        foreach ($array as $name) {
            $tag = Tag::firstOrCreateEncrypted(['tag' => $name, 'user_id' => $journal->user_id]);

            if ($tag->tagMode == 'nothing') {
                // save it, no problem:
                $journal->tags()->save($tag);
            }
            if ($tag->tagMode == 'balancingAct') {
                // if Withdrawal, journals that are of type Withdrawal, max 1.
                $withdrawal = $this->getTransactionType('Withdrawal');
                $transfer   = $this->getTransactionType('Transfer');

                $withdrawals = $tag->transactionjournals()->where('transaction_type_id', $withdrawal->id)->count();
                $transfers   = $tag->transactionjournals()->where('transaction_type_id', $transfer->id)->count();

                // only if this is the only withdrawal.
                if ($journal->transaction_type_id == $withdrawal->id && $withdrawals < 1) {
                    $journal->tags()->save($tag);
                }
                // and only if this is the only transfer
                if ($journal->transaction_type_id == $transfer->id && $transfers < 1) {
                    $journal->tags()->save($tag);
                }

                // ignore expense
            }

            if ($tag->tagMode == 'advancePayment') {
                $withdrawal  = $this->getTransactionType('Withdrawal');
                $deposit     = $this->getTransactionType('Deposit');
                $withdrawals = $tag->transactionjournals()->where('transaction_type_id', $withdrawal->id)->count();

                // only if this is the only withdrawal
                if ($journal->transaction_type_id == $withdrawal->id && $withdrawals < 1) {
                    $journal->tags()->save($tag);
                }

                // only if this is a deposit.
                if ($journal->transaction_type_id == $deposit->id) {
                    $journal->tags()->save($tag);
                }
            }

        }
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

        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->saveTags($journal, $data['tags']);
        }


        // store or get category
        if (strlen($data['category']) > 0) {
            $category = Category::firstOrCreateEncrypted(['name' => $data['category'], 'user_id' => $data['user']]);
            $journal->categories()->save($category);
        }

        // store or get budget
        if (intval($data['budget_id']) > 0) {
            $budget = Budget::find($data['budget_id']);
            $journal->budgets()->save($budget);
        }

        // store accounts (depends on type)
        list($from, $to) = $this->storeAccounts($transactionType, $data);

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
            $category = Category::firstOrCreateEncrypted(['name' => $data['category'], 'user_id' => $data['user']]);
            $journal->categories()->save($category);
        }

        // unlink all budgets and recreate them:
        $journal->budgets()->detach();
        if (intval($data['budget_id']) > 0) {
            $budget = Budget::find($data['budget_id']);
            $journal->budgets()->save($budget);
        }

        // store accounts (depends on type)
        list($from, $to) = $this->storeAccounts($journal->transactionType, $data);

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

        // update tags:
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->updateTags($journal, $data['tags']);
        }

        return $journal;
    }

    /**
     * @param TransactionJournal $journal
     * @param array              $array
     *
     * @return void
     */
    public function updateTags(TransactionJournal $journal, array $array)
    {
        // find or create all tags:
        $tags = [];
        $ids  = [];
        foreach ($array as $name) {
            $tag    = Tag::firstOrCreateEncrypted(['tag' => $name, 'user_id' => $journal->user_id]);
            $tags[] = $tag;
            $ids[]  = $tag->id;
        }

        // delete all tags connected to journal not in this array:
        if (count($ids) > 0) {
            DB::table('tag_transaction_journal')->where('transaction_journal_id', $journal->id)->whereNotIn('tag_id', $ids)->delete();
        }

        // connect each tag to journal (if not yet connected):
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            if (!$journal->tags()->find($tag->id)) {
                if ($tag->tagMode == 'nothing') {
                    // save it, no problem:
                    $journal->tags()->save($tag);
                }
                if ($tag->tagMode == 'balancingAct') {
                    // if Withdrawal, journals that are of type Withdrawal, max 1.
                    $withdrawal = $this->getTransactionType('Withdrawal');
                    $transfer   = $this->getTransactionType('Transfer');

                    $withdrawals = $tag->transactionjournals()->where('transaction_type_id', $withdrawal->id)->count();
                    $transfers   = $tag->transactionjournals()->where('transaction_type_id', $transfer->id)->count();

                    // only if this is the only withdrawal.
                    if ($journal->transaction_type_id == $withdrawal->id && $withdrawals < 1) {
                        $journal->tags()->save($tag);
                    }
                    // and only if this is the only transfer
                    if ($journal->transaction_type_id == $transfer->id && $transfers < 1) {
                        $journal->tags()->save($tag);
                        Log::debug('Saved tag! [' . $journal->transaction_type_id . ':' . $transfer->id . ':' . $transfers . ']');
                    } else {
                        Log::debug('Did not save tag. [' . $journal->transaction_type_id . ':' . $transfer->id . ':' . $transfers . ']');
                    }

                    // ignore expense
                }

                if ($tag->tagMode == 'advancePayment') {
                    $withdrawal  = $this->getTransactionType('Withdrawal');
                    $deposit     = $this->getTransactionType('Deposit');
                    $withdrawals = $tag->transactionjournals()->where('transaction_type_id', $withdrawal->id)->count();

                    // only if this is the only withdrawal
                    if ($journal->transaction_type_id == $withdrawal->id && $withdrawals < 1) {
                        $journal->tags()->save($tag);
                    }

                    // only if this is a deposit.
                    if ($journal->transaction_type_id == $deposit->id) {
                        $journal->tags()->save($tag);
                    }
                }
            }
        }
    }

    /**
     * @param TransactionType $type
     * @param array           $data
     *
     * @return array
     */
    protected function storeAccounts(TransactionType $type, array $data)
    {
        $from = null;
        $to   = null;
        switch ($type->type) {
            case 'Withdrawal':

                $from = Account::find($data['account_id']);

                if (strlen($data['expense_account']) > 0) {
                    $toType = AccountType::where('type', 'Expense account')->first();
                    $to     = Account::firstOrCreateEncrypted(
                        ['user_id' => $data['user'], 'account_type_id' => $toType->id, 'name' => $data['expense_account'], 'active' => 1]
                    );
                } else {
                    $toType = AccountType::where('type', 'Cash account')->first();
                    $to     = Account::firstOrCreateEncrypted(
                        ['user_id' => $data['user'], 'account_type_id' => $toType->id, 'name' => 'Cash account', 'active' => 1]
                    );
                }
                break;

            case 'Deposit':
                $to = Account::find($data['account_id']);

                if (strlen($data['revenue_account']) > 0) {
                    $fromType = AccountType::where('type', 'Revenue account')->first();
                    $from     = Account::firstOrCreateEncrypted(
                        ['user_id' => $data['user'], 'account_type_id' => $fromType->id, 'name' => $data['revenue_account'], 'active' => 1]
                    );
                } else {
                    $toType = AccountType::where('type', 'Cash account')->first();
                    $from   = Account::firstOrCreateEncrypted(
                        ['user_id' => $data['user'], 'account_type_id' => $toType->id, 'name' => 'Cash account', 'active' => 1]
                    );
                }

                break;
            case 'Transfer':
                $from = Account::find($data['account_from_id']);
                $to   = Account::find($data['account_to_id']);
                break;
        }
        if (is_null($to->id)) {
            Log::error('"to"-account is null, so we cannot continue!');
            App::abort(500, '"to"-account is null, so we cannot continue!');
        }
        if (is_null($from->id)) {
            Log::error('"from"-account is null, so we cannot continue!');
            App::abort(500, '"from"-account is null, so we cannot continue!');
        }

        return [$from, $to];
    }
}
