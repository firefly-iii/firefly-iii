<?php
declare(strict_types = 1);

namespace FireflyIII\Helpers\Collector;


use Carbon\Carbon;
use Crypt;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
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
class JournalCollector
{

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
    /** @var array */
    private $group
        = [
            'transaction_journals.id',
            'transaction_journals.description',
            'firefly-iii.transaction_journals.date',
            'transaction_journals.encrypted',
            'transaction_currencies.code',
            'transaction_types.type',
            'transaction_journals.bill_id',
            'bills.name',
            'transactions.amount',
        ];
    /** @var  bool */
    private $joinedBudget = false;
    /** @var  bool */
    private $joinedCategory = false;
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
        $countQuery->groupBy('accounts.user_id');
        $this->count = $countQuery->count();

        return $this->count;
    }

    /**
     * @return Collection
     */
    public function getJournals(): Collection
    {
        $this->run = true;
        $set       = $this->query->get(array_values($this->fields));

        // filter out transfers:
        if ($this->filterTransfers) {
            $set = $set->filter(
                function (Transaction $transaction) {
                    if (!($transaction->transaction_type_type === TransactionType::TRANSFER && bccomp($transaction->transaction_amount, '0') === -1)) {
                        return $transaction;
                    }

                    return false;
                }
            );
        }

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
     * @return JournalCollector
     */
    public function setAccounts(Collection $accounts): JournalCollector
    {
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $this->query->whereIn('transactions.account_id', $accountIds);
        }

        if ($accounts->count() > 1) {
            $this->filterTransfers = true;
        }

        return $this;
    }

    /**
     * @return JournalCollector
     */
    public function setAllAssetAccounts(): JournalCollector
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class, [$this->user]);
        $accounts   = $repository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT, AccountType::CASH]);
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $this->query->whereIn('transactions.account_id', $accountIds);
        }

        if ($accounts->count() > 1) {
            $this->filterTransfers = true;
        }

        return $this;
    }

    /**
     * @param Collection $bills
     *
     * @return JournalCollector
     */
    public function setBills(Collection $bills): JournalCollector
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
     * @return JournalCollector
     */
    public function setBudget(Budget $budget): JournalCollector
    {
        if (!$this->joinedBudget) {
            // join some extra tables:
            $this->joinedBudget = true;
            $this->query->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
            $this->query->leftJoin('budget_transaction', 'budget_transaction.transaction_id', '=', 'transactions.id');
        }

        $this->query->where(
            function (EloquentBuilder $q) use ($budget) {
                $q->where('budget_transaction.budget_id', $budget->id);
                $q->orWhere('budget_transaction_journal.budget_id', $budget->id);
            }
        );

        return $this;
    }

    /**
     * @param Category $category
     *
     * @return JournalCollector
     */
    public function setCategory(Category $category): JournalCollector
    {
        if (!$this->joinedCategory) {
            // join some extra tables:
            $this->joinedCategory = true;
            $this->query->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
            $this->query->leftJoin('category_transaction', 'category_transaction.transaction_id', '=', 'transactions.id');
        }

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
     * @return JournalCollector
     */
    public function setLimit(int $limit): JournalCollector
    {
        $this->limit = $limit;
        $this->query->limit($limit);
        Log::debug(sprintf('Set limit to %d', $limit));

        return $this;
    }

    /**
     * @param int $offset
     *
     * @return JournalCollector
     */
    public function setOffset(int $offset): JournalCollector
    {
        $this->offset = $offset;
    }

    /**
     * @param int $page
     *
     * @return JournalCollector
     */
    public function setPage(int $page): JournalCollector
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
     * @return JournalCollector
     */
    public function setRange(Carbon $start, Carbon $end): JournalCollector
    {
        if ($start <= $end) {
            $this->query->where('transaction_journals.date', '>=', $start->format('Y-m-d'));
            $this->query->where('transaction_journals.date', '<=', $end->format('Y-m-d'));
        }

        return $this;
    }

    /**
     * @param array $types
     *
     * @return JournalCollector
     */
    public function setTypes(array $types): JournalCollector
    {
        if (count($types) > 0) {
            $this->query->whereIn('transaction_types.type', $types);
        }

        return $this;
    }

    /**
     * @return JournalCollector
     */
    public function withoutCategory(): JournalCollector
    {
        if (!$this->joinedCategory) {
            // join some extra tables:
            $this->joinedCategory = true;
            $this->query->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
            $this->query->leftJoin('category_transaction', 'category_transaction.transaction_id', '=', 'transactions.id');
        }

        $this->query->where(
            function (EloquentBuilder $q) {
                $q->whereNull('category_transaction.category_id');
                $q->whereNull('category_transaction_journal.category_id');
            }
        );

        return $this;
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