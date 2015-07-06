<?php

namespace FireflyIII\Repositories\Journal;

use App;
use Auth;
use Carbon\Carbon;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Pagination\LengthAwarePaginator;
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
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function delete(TransactionJournal $journal)
    {
        $journal->delete();

        return true;
    }

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
     * @param TransactionJournal $journal
     * @param Transaction        $transaction
     *
     * @return integer
     */
    public function getAmountBefore(TransactionJournal $journal, Transaction $transaction)
    {
        $set = $transaction->account->transactions()->leftJoin(
            'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
        )
                                    ->where('transaction_journals.date', '<=', $journal->date->format('Y-m-d'))
                                    ->where('transaction_journals.order', '>=', $journal->order)
                                    ->where('transaction_journals.id', '!=', $journal->id)
                                    ->get(['transactions.*']);
        $sum = 0;
        foreach ($set as $entry) {
            $sum += $entry->amount;
        }

        return $sum;

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
     * @param array $types
     * @param int   $offset
     * @param int   $page
     *
     * @return LengthAwarePaginator
     */
    public function getJournalsOfTypes(array $types, $offset, $page)
    {
        $set      = Auth::user()->transactionJournals()->transactionTypes($types)->withRelevantData()->take(50)->offset($offset)
                        ->orderBy('date', 'DESC')
                        ->orderBy('order', 'ASC')
                        ->orderBy('id', 'DESC')
                        ->get(
                            ['transaction_journals.*']
                        );
        $count    = Auth::user()->transactionJournals()->transactionTypes($types)->count();
        $journals = new LengthAwarePaginator($set, $count, 50, $page);

        return $journals;
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
     * @param        $id
     * @param Carbon $date
     *
     * @return TransactionJournal
     */
    public function getWithDate($id, Carbon $date)
    {
        return Auth::user()->transactionjournals()->where('id', $id)->where('date', $date->format('Y-m-d 00:00:00'))->first();
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
        /** @var \FireflyIII\Repositories\Tag\TagRepositoryInterface $tagRepository */
        $tagRepository = App::make('FireflyIII\Repositories\Tag\TagRepositoryInterface');

        foreach ($array as $name) {
            if (strlen(trim($name)) > 0) {
                $tag = Tag::firstOrCreateEncrypted(['tag' => $name, 'user_id' => $journal->user_id]);
                if (!is_null($tag)) {
                    $tagRepository->connect($journal, $tag);
                }
            }
        }
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
            $category = Category::firstOrCreateEncrypted(['name' => $data['category'], 'user_id' => $data['user']]);
            $journal->categories()->save($category);
        }

        // store or get budget
        if (intval($data['budget_id']) > 0) {
            /** @var \FireflyIII\Models\Budget $budget */
            $budget = Budget::find($data['budget_id']);
            $journal->budgets()->save($budget);
        }

        // store accounts (depends on type)
        list($fromAccount, $toAccount) = $this->storeAccounts($transactionType, $data);

        // store accompanying transactions.
        Transaction::create( // first transaction.
            [
                'account_id'             => $fromAccount->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $data['amount'] * -1
            ]
        );
        Transaction::create( // second transaction.
            [
                'account_id'             => $toAccount->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $data['amount']
            ]
        );
        $journal->completed = 1;
        $journal->save();

        // store tags
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->saveTags($journal, $data['tags']);
        }

        return $journal;


    }

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
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
            /** @var \FireflyIII\Models\Budget $budget */
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
        // create tag repository
        /** @var \FireflyIII\Repositories\Tag\TagRepositoryInterface $tagRepository */
        $tagRepository = App::make('FireflyIII\Repositories\Tag\TagRepositoryInterface');


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
    }

    /**
     * @param TransactionType $type
     * @param array           $data
     *
     * @return array
     */
    protected function storeAccounts(TransactionType $type, array $data)
    {
        $fromAccount = null;
        $toAccount   = null;
        switch ($type->type) {
            case 'Withdrawal':
                list($fromAccount, $toAccount) = $this->storeWithdrawalAccounts($data);
                break;

            case 'Deposit':
                list($fromAccount, $toAccount) = $this->storeDepositAccounts($data);

                break;
            case 'Transfer':
                $fromAccount = Account::find($data['account_from_id']);
                $toAccount   = Account::find($data['account_to_id']);
                break;
        }

        if (is_null($toAccount)) {
            Log::error('"to"-account is null, so we cannot continue!');
            App::abort(500, '"to"-account is null, so we cannot continue!');
            // @codeCoverageIgnoreStart
        }
        // @codeCoverageIgnoreEnd

        if (is_null($fromAccount)) {
            Log::error('"from"-account is null, so we cannot continue!');
            App::abort(500, '"from"-account is null, so we cannot continue!');
            // @codeCoverageIgnoreStart
        }

        // @codeCoverageIgnoreEnd

        return [$fromAccount, $toAccount];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function storeWithdrawalAccounts(array $data)
    {
        $fromAccount = Account::find($data['account_id']);

        if (strlen($data['expense_account']) > 0) {
            $toType    = AccountType::where('type', 'Expense account')->first();
            $toAccount = Account::firstOrCreateEncrypted(
                ['user_id' => $data['user'], 'account_type_id' => $toType->id, 'name' => $data['expense_account'], 'active' => 1]
            );
        } else {
            $toType    = AccountType::where('type', 'Cash account')->first();
            $toAccount = Account::firstOrCreateEncrypted(
                ['user_id' => $data['user'], 'account_type_id' => $toType->id, 'name' => 'Cash account', 'active' => 1]
            );
        }

        return [$fromAccount, $toAccount];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function storeDepositAccounts(array $data)
    {
        $toAccount = Account::find($data['account_id']);

        if (strlen($data['revenue_account']) > 0) {
            $fromType    = AccountType::where('type', 'Revenue account')->first();
            $fromAccount = Account::firstOrCreateEncrypted(
                ['user_id' => $data['user'], 'account_type_id' => $fromType->id, 'name' => $data['revenue_account'], 'active' => 1]
            );
        } else {
            $toType      = AccountType::where('type', 'Cash account')->first();
            $fromAccount = Account::firstOrCreateEncrypted(
                ['user_id' => $data['user'], 'account_type_id' => $toType->id, 'name' => 'Cash account', 'active' => 1]
            );
        }

        return [$fromAccount, $toAccount];
    }
}
