<?php
declare(strict_types = 1);

namespace FireflyIII\Helpers\Collector;


use Carbon\Carbon;
use Crypt;
use DB;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;

/**
 * Maybe this is a good idea after all...
 *
 * Class JournalCollector
 *
 * @package FireflyIII\Helpers\Collector
 */
class JournalCollector implements JournalCollectorInterface
{

    /** @var array */
    private $accountIds = [];
    /** @var  int */
    private $count = 0;
    /** @var array */
    private $fields
        = [
            'transaction_journals.id as journal_id',
            'transaction_journals.description',
            'transaction_journals.date',
            'transaction_journals.encrypted',
            //'transaction_journals.transaction_currency_id',
            'transaction_currencies.code as transaction_currency_code',
            //'transaction_currencies.symbol as transaction_currency_symbol',
            'transaction_types.type as transaction_type_type',
            'transaction_journals.bill_id',
            'bills.name as bill_name',
            'transactions.id as id',
            'transactions.amount as transaction_amount',
            'transactions.description as transaction_description',
            'transactions.account_id',
            'transactions.identifier',
            'transactions.transaction_journal_id',
            'accounts.name as account_name',
            'accounts.encrypted as account_encrypted',
            'account_types.type as account_type',
        ];
    /** @var  bool */
    private $filterTransfers = false;
    /** @var  bool */
    private $joinedBudget = false;
    /** @var  bool */
    private $joinedCategory = false;
    /** @var bool */
    private $joinedOpposing = false;
    /** @var bool */
    private $joinedTag = false;
    /** @var  int */
    private $limit;
    /** @var  int */
    private $offset;
    /** @var int */
    private $page = 1;
    /** @var EloquentBuilder */
    private $query;
    /** @var bool */
    private $run = false;
    /** @var User */
    private $user;

    /**
     * JournalCollector constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user  = $user;
        $this->query = $this->startQuery();
    }

    /**
     * @return int
     * @throws FireflyException
     */
    public function count(): int
    {
        if ($this->run === true) {
            throw new FireflyException('Cannot count after run in JournalCollector.');
        }

        $countQuery = clone $this->query;

        // dont need some fields:
        $countQuery->getQuery()->limit      = null;
        $countQuery->getQuery()->offset     = null;
        $countQuery->getQuery()->unionLimit = null;
        $countQuery->getQuery()->groups     = null;
        $countQuery->getQuery()->orders     = null;
        $countQuery->groupBy('accounts.user_id');
        $this->count = $countQuery->count();

        return $this->count;
    }

    /**
     * @return JournalCollectorInterface
     */
    public function disableFilter(): JournalCollectorInterface
    {
        $this->filterTransfers = false;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getJournals(): Collection
    {
        $this->run = true;
        $set       = $this->query->get(array_values($this->fields));
        $set       = $this->filterTransfers($set);

        // loop for decryption.
        $set->each(
            function (Transaction $transaction) {
                $transaction->date        = new Carbon($transaction->date);
                $transaction->description = intval($transaction->encrypted) === 1 ? Crypt::decrypt($transaction->description) : $transaction->description;
                $transaction->bill_name   = !is_null($transaction->bill_name) ? Crypt::decrypt($transaction->bill_name) : '';
            }
        );

        return $set;
    }

    /**
     * @return LengthAwarePaginator
     * @throws FireflyException
     */
    public function getPaginatedJournals():LengthAwarePaginator
    {
        if ($this->run === true) {
            throw new FireflyException('Cannot getPaginatedJournals after run in JournalCollector.');
        }
        $this->count();
        $set      = $this->getJournals();
        $journals = new LengthAwarePaginator($set, $this->count, $this->limit, $this->page);

        return $journals;
    }

    /**
     * @param Collection $accounts
     *
     * @return JournalCollectorInterface
     */
    public function setAccounts(Collection $accounts): JournalCollectorInterface
    {
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $this->query->whereIn('transactions.account_id', $accountIds);
            Log::debug(sprintf('setAccounts: %s', join(', ', $accountIds)));
            $this->accountIds = $accountIds;
        }

        if ($accounts->count() > 1) {
            $this->filterTransfers = true;
        }


        return $this;
    }

    /**
     * @return JournalCollectorInterface
     */
    public function setAllAssetAccounts(): JournalCollectorInterface
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class, [$this->user]);
        $accounts   = $repository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $this->query->whereIn('transactions.account_id', $accountIds);
            $this->accountIds = $accountIds;
        }

        if ($accounts->count() > 1) {
            $this->filterTransfers = true;
        }

        return $this;
    }

    /**
     * @param Collection $bills
     *
     * @return JournalCollectorInterface
     */
    public function setBills(Collection $bills): JournalCollectorInterface
    {
        if ($bills->count() > 0) {
            $billIds = $bills->pluck('id')->toArray();
            $this->query->whereIn('transaction_journals.bill_id', $billIds);
        }

        return $this;

    }

    /**
     * @param Budget $budget
     *
     * @return JournalCollectorInterface
     */
    public function setBudget(Budget $budget): JournalCollectorInterface
    {
        $this->joinBudgetTables();

        $this->query->where(
            function (EloquentBuilder $q) use ($budget) {
                $q->where('budget_transaction.budget_id', $budget->id);
                $q->orWhere('budget_transaction_journal.budget_id', $budget->id);
            }
        );

        return $this;
    }

    /**
     * @param Collection $categories
     *
     * @return JournalCollectorInterface
     */
    public function setCategories(Collection $categories): JournalCollectorInterface
    {
        $categoryIds = $categories->pluck('id')->toArray();
        if (count($categoryIds) === 0) {
            return $this;
        }
        $this->joinCategoryTables();

        $this->query->where(
            function (EloquentBuilder $q) use ($categoryIds) {
                $q->whereIn('category_transaction.category_id', $categoryIds);
                $q->orWhereIn('category_transaction_journal.category_id', $categoryIds);
            }
        );

        return $this;
    }

    /**
     * @param Category $category
     *
     * @return JournalCollectorInterface
     */
    public function setCategory(Category $category): JournalCollectorInterface
    {
        $this->joinCategoryTables();

        $this->query->where(
            function (EloquentBuilder $q) use ($category) {
                $q->where('category_transaction.category_id', $category->id);
                $q->orWhere('category_transaction_journal.category_id', $category->id);
            }
        );

        return $this;
    }

    /**
     * @param int $limit
     *
     * @return JournalCollectorInterface
     */
    public function setLimit(int $limit): JournalCollectorInterface
    {
        $this->limit = $limit;
        $this->query->limit($limit);
        Log::debug(sprintf('Set limit to %d', $limit));

        return $this;
    }

    /**
     * @param int $offset
     *
     * @return JournalCollectorInterface
     */
    public function setOffset(int $offset): JournalCollectorInterface
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @param int $page
     *
     * @return JournalCollectorInterface
     */
    public function setPage(int $page): JournalCollectorInterface
    {
        $this->page = $page;

        if ($page > 0) {
            $page--;
        }
        Log::debug(sprintf('Page is %d', $page));

        if (!is_null($this->limit)) {
            $offset       = ($this->limit * $page);
            $this->offset = $offset;
            $this->query->skip($offset);
            Log::debug(sprintf('Changed offset to %d', $offset));
        }
        if (is_null($this->limit)) {
            Log::debug('The limit is zero, cannot set the page.');
        }

        return $this;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return JournalCollectorInterface
     */
    public function setRange(Carbon $start, Carbon $end): JournalCollectorInterface
    {
        if ($start <= $end) {
            $this->query->where('transaction_journals.date', '>=', $start->format('Y-m-d'));
            $this->query->where('transaction_journals.date', '<=', $end->format('Y-m-d'));
        }

        return $this;
    }

    /**
     * @param Tag $tag
     *
     * @return JournalCollectorInterface
     */
    public function setTag(Tag $tag): JournalCollectorInterface
    {
        $this->joinTagTables();
        $this->query->where('tag_transaction_journal.tag_id', $tag->id);

        return $this;
    }

    /**
     * @param array $types
     *
     * @return JournalCollectorInterface
     */
    public function setTypes(array $types): JournalCollectorInterface
    {
        if (count($types) > 0) {
            $this->query->whereIn('transaction_types.type', $types);
        }

        return $this;
    }

    /**
     * @return JournalCollectorInterface
     */
    public function withOpposingAccount(): JournalCollectorInterface
    {
        $this->joinOpposingTables();

        $accountIds = $this->accountIds;
        $this->query->where(
            function (EloquentBuilder $q1) use ($accountIds) {
                // set 1
                $q1->where(
                    function (EloquentBuilder $q2) use ($accountIds) {
                        // transactions.account_id in set
                        $q2->whereIn('transactions.account_id', $accountIds);
                        // opposing.account_id not in set
                        $q2->whereNotIn('opposing.account_id', $accountIds);

                    }
                );
                // or set 2
                $q1->orWhere(
                    function (EloquentBuilder $q3) use ($accountIds) {
                        // transactions.account_id not in set
                        $q3->whereNotIn('transactions.account_id', $accountIds);
                        // B in set
                        // opposing.account_id not in set
                        $q3->whereIn('opposing.account_id', $accountIds);
                    }
                );
            }
        );

        return $this;
    }

    /**
     * @return JournalCollectorInterface
     */
    public function withoutBudget(): JournalCollectorInterface
    {
        $this->joinBudgetTables();

        $this->query->where(
            function (EloquentBuilder $q) {
                $q->whereNull('budget_transaction.budget_id');
                $q->whereNull('budget_transaction_journal.budget_id');
            }
        );

        return $this;
    }

    /**
     * @return JournalCollectorInterface
     */
    public function withoutCategory(): JournalCollectorInterface
    {
        $this->joinCategoryTables();

        $this->query->where(
            function (EloquentBuilder $q) {
                $q->whereNull('category_transaction.category_id');
                $q->whereNull('category_transaction_journal.category_id');
            }
        );

        return $this;
    }

    /**
     * If the set of accounts used by the collector includes more than one asset
     * account, chances are the set include double entries: transfers get selected
     * on both the source, and then again on the destination account.
     *
     * This method filters them out.
     *
     * @param Collection $set
     *
     * @return Collection
     */
    private function filterTransfers(Collection $set): Collection
    {
        if ($this->filterTransfers) {
            $set = $set->filter(
                function (Transaction $transaction) {
                    if (!($transaction->transaction_type_type === TransactionType::TRANSFER && bccomp($transaction->transaction_amount, '0') === -1)) {

                        Log::debug(
                            sprintf(
                                'Included journal #%d (transaction #%d) because its a %s with amount %f',
                                $transaction->transaction_journal_id,
                                $transaction->id,
                                $transaction->transaction_type_type,
                                $transaction->transaction_amount
                            )
                        );

                        return $transaction;
                    }

                    Log::debug(
                        sprintf(
                            'Removed journal #%d (transaction #%d) because its a %s with amount %f',
                            $transaction->transaction_journal_id,
                            $transaction->id,
                            $transaction->transaction_type_type,
                            $transaction->transaction_amount
                        )
                    );

                    return false;
                }
            );
        }

        return $set;
    }

    /**
     *
     */
    private function joinBudgetTables()
    {
        if (!$this->joinedBudget) {
            // join some extra tables:
            $this->joinedBudget = true;
            $this->query->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
            $this->query->leftJoin('budget_transaction', 'budget_transaction.transaction_id', '=', 'transactions.id');
        }
    }

    /**
     *
     */
    private function joinCategoryTables()
    {
        if (!$this->joinedCategory) {
            // join some extra tables:
            $this->joinedCategory = true;
            $this->query->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
            $this->query->leftJoin('category_transaction', 'category_transaction.transaction_id', '=', 'transactions.id');
            $this->fields[] = 'category_transaction_journal.category_id as transaction_journal_category_id';
            $this->fields[] = 'category_transaction.category_id as transaction_category_id';
        }
    }

    private function joinOpposingTables()
    {
        if (!$this->joinedOpposing) {
            // join opposing transaction (hard):
            $this->query->leftJoin(
                'transactions as opposing', function (JoinClause $join) {
                $join->on('opposing.transaction_journal_id', '=', 'transactions.transaction_journal_id')
                     ->where('opposing.identifier', '=', 'transactions.identifier')
                     ->where('opposing.amount', '=', DB::raw('transactions.amount * -1'));
            }
            );
            $this->query->leftJoin('accounts as opposing_accounts', 'opposing.account_id', '=', 'opposing_accounts.id');
            $this->query->leftJoin('account_types as opposing_account_types', 'opposing_accounts.account_type_id', '=', 'opposing_account_types.id');

            $this->fields[] = 'opposing.account_id as opposing_account_id';
            $this->fields[] = 'opposing_accounts.name as opposing_account_name';
            $this->fields[] = 'opposing_accounts.encrypted as opposing_account_encrypted';
            $this->fields[] = 'opposing_account_types.type as opposing_account_type';

        }
    }

    /**
     *
     */
    private function joinTagTables()
    {
        if (!$this->joinedTag) {
            // join some extra tables:
            $this->joinedTag = true;
            $this->query->leftJoin('tag_transaction_journal', 'tag_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
        }
    }

    /**
     * @return EloquentBuilder
     */
    private function startQuery(): EloquentBuilder
    {

        $query = Transaction
            ::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->leftJoin('transaction_currencies', 'transaction_currencies.id', 'transaction_journals.transaction_currency_id')
            ->leftJoin('transaction_types', 'transaction_types.id', 'transaction_journals.transaction_type_id')
            ->leftJoin('bills', 'bills.id', 'transaction_journals.bill_id')
            ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->leftJoin('account_types', 'accounts.account_type_id', 'account_types.id')
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_journals.deleted_at')
            ->where('transaction_journals.user_id', $this->user->id)
            ->orderBy('transaction_journals.date', 'DESC')
            ->orderBy('transaction_journals.order', 'ASC')
            ->orderBy('transaction_journals.id', 'DESC');

        return $query;

    }
}