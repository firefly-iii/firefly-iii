<?php
/**
 * JournalRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Repositories\Journal;

use Carbon\Carbon;
use DB;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
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
    /** @var User */
    private $user;

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
     * Returns the amount in the account before the specified transaction took place.
     *
     * @param Transaction $transaction
     *
     * @return string
     */
    public function balanceBeforeTransaction(Transaction $transaction): string
    {
        // some dates from journal
        $journal = $transaction->transactionJournal;
        $query   = Transaction::
        leftJoin('transaction_journals', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                              ->where('transactions.account_id', $transaction->account_id)
                              ->where('transaction_journals.user_id', $this->user->id)
                              ->where(
                                  function (Builder $q) use ($journal) {
                                      $q->where('transaction_journals.date', '<', $journal->date->format('Y-m-d'));
                                      $q->orWhere(
                                          function (Builder $qq) use ($journal) {
                                              $qq->where('transaction_journals.date', '=', $journal->date->format('Y-m-d'));
                                              $qq->where('transaction_journals.order', '>', $journal->order);
                                          }
                                      );

                                  }
                              )
                              ->where('transactions.id', '!=', $transaction->id)
                              ->whereNull('transactions.deleted_at')
                              ->whereNull('transaction_journals.deleted_at')
                              ->orderBy('transaction_journals.date', 'DESC')
                              ->orderBy('transaction_journals.order', 'ASC')
                              ->orderBy('transaction_journals.id', 'DESC');
        $sum     = $query->sum('transactions.amount');

        return strval($sum);
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
        $journal = $this->user->transactionjournals()->where('id', $journalId)->first();
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
        $entry = $this->user->transactionjournals()->orderBy('date', 'ASC')->first(['transaction_journals.*']);

        if (is_null($entry)) {

            return new TransactionJournal;
        }

        return $entry;
    }

    /**
     * @param array $types
     * @param int   $page
     * @param int   $pageSize
     *
     * @return LengthAwarePaginator
     */
    public function getJournals(array $types, int $page, int $pageSize = 50): LengthAwarePaginator
    {
        $offset = ($page - 1) * $pageSize;
        $query  = $this->user->transactionJournals()->expanded()->sortCorrectly();
        if (count($types) > 0) {
            $query->transactionTypes($types);
        }
        $count    = $this->user->transactionJournals()->transactionTypes($types)->count();
        $set      = $query->take($pageSize)->offset($offset)->get(TransactionJournal::queryFields());
        $journals = new LengthAwarePaginator($set, $count, $pageSize, $page);

        return $journals;
    }

    /**
     * Returns a collection of ALL journals, given a specific account and a date range.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function getJournalsInRange(Collection $accounts, Carbon $start, Carbon $end): Collection
    {
        $query = $this->user->transactionJournals()->expanded()->sortCorrectly();
        $query->before($end);
        $query->after($start);

        if ($accounts->count() > 0) {
            $ids = $accounts->pluck('id')->toArray();
            // join source and destination:
            $query->leftJoin(
                'transactions as source', function (JoinClause $join) {
                $join->on('source.transaction_journal_id', '=', 'transaction_journals.id')->where('source.amount', '<', 0);
            }
            );
            $query->leftJoin(
                'transactions as destination', function (JoinClause $join) {
                $join->on('destination.transaction_journal_id', '=', 'transaction_journals.id')->where('destination.amount', '>', 0);
            }
            );

            $query->where(
                function (Builder $q) use ($ids) {
                    $q->whereIn('destination.account_id', $ids);
                    $q->orWhereIn('source.account_id', $ids);
                }
            );
        }

        $set = $query->get(TransactionJournal::queryFields());

        return $set;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public function getPiggyBankEvents(TransactionJournal $journal): Collection
    {
        /** @var Collection $set */
        $events = $journal->piggyBankEvents()->get();
        $events->each(
            function (PiggyBankEvent $event) {
                $event->piggyBank = $event->piggyBank()->withTrashed()->first();
            }
        );

        return $events;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public function getTransactions(TransactionJournal $journal): Collection
    {
        $transactions = new Collection;
        switch ($journal->transactionType->type) {
            case TransactionType::DEPOSIT:
                /** @var Collection $transactions */
                $transactions = $journal->transactions()
                                        ->groupBy('transactions.account_id')
                                        ->where('amount', '<', 0)
                                        ->groupBy('transactions.id')
                                        ->orderBy('amount', 'ASC')->get(
                        ['transactions.*', DB::raw('SUM(`transactions`.`amount`) as `sum`')]
                    );
                $final        = $journal->transactions()
                                        ->groupBy('transactions.account_id')
                                        ->where('amount', '>', 0)
                                        ->orderBy('amount', 'ASC')->first(
                        ['transactions.*', DB::raw('SUM(`transactions`.`amount`) as `sum`')]
                    );
                $transactions->push($final);
                break;
            case TransactionType::TRANSFER:

                /** @var Collection $transactions */
                $transactions = $journal->transactions()
                                        ->groupBy('transactions.id')
                                        ->orderBy('transactions.id')->get(
                        ['transactions.*', DB::raw('SUM(`transactions`.`amount`) as `sum`')]
                    );
                break;
            case TransactionType::WITHDRAWAL:

                /** @var Collection $transactions */
                $transactions = $journal->transactions()
                                        ->where('amount', '>', 0)
                                        ->groupBy('transactions.id')
                                        ->orderBy('amount', 'ASC')->get(
                        ['transactions.*', DB::raw('SUM(`transactions`.`amount`) as `sum`')]
                    );
                $final        = $journal->transactions()
                                        ->where('amount', '<', 0)
                                        ->groupBy('transactions.account_id')
                                        ->orderBy('amount', 'ASC')->first(
                        ['transactions.*', DB::raw('SUM(`transactions`.`amount`) as `sum`')]
                    );
                $transactions->push($final);
                break;
        }
        // foreach do balance thing
        $transactions->each(
            function (Transaction $t) {
                $t->before = $this->balanceBeforeTransaction($t);
            }
        );

        return $transactions;
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

        // store actual journal.
        $journal = new TransactionJournal(
            [
                'user_id'                 => $data['user'],
                'transaction_type_id'     => $transactionType->id,
                'transaction_currency_id' => $data['amount_currency_id_amount'],
                'description'             => $data['description'],
                'completed'               => 0,
                'date'                    => $data['date'],
                'interest_date'           => $data['interest_date'],
                'book_date'               => $data['book_date'],
                'process_date'            => $data['process_date'],
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
        list($sourceAccount, $destinationAccount) = $this->storeAccounts($transactionType, $data);

        // store accompanying transactions.
        Transaction::create( // first transaction.
            [
                'account_id'             => $sourceAccount->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $data['amount'] * -1,
            ]
        );
        Transaction::create( // second transaction.
            [
                'account_id'             => $destinationAccount->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $data['amount'],
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
                'interest_date'           => $data['interest_date'],
                'book_date'               => $data['book_date'],
                'process_date'            => $data['process_date'],
            ]
        );
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
        // update actual journal.
        $journal->transaction_currency_id = $data['amount_currency_id_amount'];
        $journal->description             = $data['description'];
        $journal->date                    = $data['date'];
        $journal->interest_date           = $data['interest_date'];
        $journal->book_date               = $data['book_date'];
        $journal->process_date            = $data['process_date'];


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
            $budget = Budget::where('user_id', $this->user->id)->where('id', $data['budget_id'])->first();
            $journal->budgets()->save($budget);
        }

        // store accounts (depends on type)
        list($fromAccount, $toAccount) = $this->storeAccounts($journal->transactionType, $data);

        // update the from and to transaction.
        /** @var Transaction $transaction */
        foreach ($journal->transactions()->get() as $transaction) {
            if ($transaction->amount < 0) {
                // this is the from transaction, negative amount:
                $transaction->amount     = $data['amount'] * -1;
                $transaction->account_id = $fromAccount->id;
                $transaction->save();
            }
            if ($transaction->amount > 0) {
                $transaction->amount     = $data['amount'];
                $transaction->account_id = $toAccount->id;
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
        $sourceAccount      = null;
        $destinationAccount = null;
        switch ($type->type) {
            case TransactionType::WITHDRAWAL:
                list($sourceAccount, $destinationAccount) = $this->storeWithdrawalAccounts($data);
                break;

            case TransactionType::DEPOSIT:
                list($sourceAccount, $destinationAccount) = $this->storeDepositAccounts($data);

                break;
            case TransactionType::TRANSFER:
                $sourceAccount      = Account::where('user_id', $this->user->id)->where('id', $data['source_account_id'])->first();
                $destinationAccount = Account::where('user_id', $this->user->id)->where('id', $data['destination_account_id'])->first();
                break;
            default:
                throw new FireflyException('Did not recognise transaction type.');
        }

        if (is_null($destinationAccount)) {
            Log::error('"destination"-account is null, so we cannot continue!', ['data' => $data]);
            throw new FireflyException('"destination"-account is null, so we cannot continue!');
        }

        if (is_null($sourceAccount)) {
            Log::error('"source"-account is null, so we cannot continue!', ['data' => $data]);
            throw new FireflyException('"source"-account is null, so we cannot continue!');

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
                ['user_id' => $data['user'], 'account_type_id' => $fromType->id, 'name' => $data['source_account_name'], 'active' => 1]
            );

            return [$fromAccount, $destinationAccount];
        }
        $fromType    = AccountType::where('type', 'Cash account')->first();
        $fromAccount = Account::firstOrCreateEncrypted(
            ['user_id' => $data['user'], 'account_type_id' => $fromType->id, 'name' => 'Cash account', 'active' => 1]
        );

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
                    'user_id'         => $data['user'],
                    'account_type_id' => $destinationType->id,
                    'name'            => $data['destination_account_name'],
                    'active'          => 1,
                ]
            );

            return [$sourceAccount, $destinationAccount];
        }
        $destinationType    = AccountType::where('type', 'Cash account')->first();
        $destinationAccount = Account::firstOrCreateEncrypted(
            ['user_id' => $data['user'], 'account_type_id' => $destinationType->id, 'name' => 'Cash account', 'active' => 1]
        );

        return [$sourceAccount, $destinationAccount];


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
