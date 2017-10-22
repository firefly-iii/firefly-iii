<?php
/**
 * JournalCollector.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Collector;


use Carbon\Carbon;
use DB;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Filter\FilterInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Helpers\Filter\NegativeAmountFilter;
use FireflyIII\Helpers\Filter\OpposingAccountFilter;
use FireflyIII\Helpers\Filter\PositiveAmountFilter;
use FireflyIII\Helpers\Filter\TransferFilter;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use Steam;

/**
 * Maybe this is a good idea after all...
 *
 * Class JournalCollector
 *
 * @package FireflyIII\Helpers\Collector
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
            'transaction_types.type as transaction_type_type',
            'transaction_journals.bill_id',
            'bills.name as bill_name',
            'bills.name_encrypted as bill_name_encrypted',

            'transactions.id as id',
            'transactions.description as transaction_description',
            'transactions.account_id',
            'transactions.identifier',
            'transactions.transaction_journal_id',
            'transactions.amount as transaction_amount',
            'transactions.transaction_currency_id as transaction_currency_id',

            'transaction_currencies.code as transaction_currency_code',
            'transaction_currencies.symbol as transaction_currency_symbol',
            'transaction_currencies.decimal_places as transaction_currency_dp',

            'transactions.foreign_amount as transaction_foreign_amount',
            'transactions.foreign_currency_id as foreign_currency_id',

            'foreign_currencies.code as foreign_currency_code',
            'foreign_currencies.symbol as foreign_currency_symbol',
            'foreign_currencies.decimal_places as foreign_currency_dp',

            'accounts.name as account_name',
            'accounts.encrypted as account_encrypted',
            'accounts.iban as account_iban',
            'account_types.type as account_type',

        ];
    /** @var array */
    private $filters = [InternalTransferFilter::class];

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
     * @param string $filter
     *
     * @return JournalCollectorInterface
     */
    public function addFilter(string $filter): JournalCollectorInterface
    {
        $interfaces = class_implements($filter);
        if (in_array(FilterInterface::class, $interfaces) && !in_array($filter, $this->filters) ) {
            Log::debug(sprintf('Enabled filter %s', $filter));
            $this->filters[] = $filter;
        }

        return $this;
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
     * @return Collection
     */
    public function getJournals(): Collection
    {
        $this->run = true;
        /** @var Collection $set */
        $set = $this->query->get(array_values($this->fields));

        // run all filters:
        $set = $this->filter($set);

        // loop for decryption.
        $set->each(
            function (Transaction $transaction) {
                $transaction->date        = new Carbon($transaction->date);
                $transaction->description = Steam::decrypt(intval($transaction->encrypted), $transaction->description);

                if (!is_null($transaction->bill_name)) {
                    $transaction->bill_name = Steam::decrypt(intval($transaction->bill_name_encrypted), $transaction->bill_name);
                }
                $transaction->opposing_account_name = app('steam')->tryDecrypt($transaction->opposing_account_name);
                $transaction->account_iban          = app('steam')->tryDecrypt($transaction->account_iban);
                $transaction->opposing_account_iban = app('steam')->tryDecrypt($transaction->opposing_account_iban);


            }
        );

        return $set;
    }

    /**
     * @return LengthAwarePaginator
     * @throws FireflyException
     */
    public function getPaginatedJournals(): LengthAwarePaginator
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
     * @param string $filter
     *
     * @return JournalCollectorInterface
     */
    public function removeFilter(string $filter): JournalCollectorInterface
    {
        $key = array_search($filter, $this->filters, true);
        if (!($key === false)) {
            Log::debug(sprintf('Removed filter %s', $filter));
            unset($this->filters[$key]);
        }

        return $this;
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
            $this->addFilter(TransferFilter::class);
        }


        return $this;
    }

    /**
     * @return JournalCollectorInterface
     */
    public function setAllAssetAccounts(): JournalCollectorInterface
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser($this->user);
        $accounts = $repository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $this->query->whereIn('transactions.account_id', $accountIds);
            $this->accountIds = $accountIds;
        }

        if ($accounts->count() > 1) {
            $this->addFilter(TransferFilter::class);
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
     * @param Collection $budgets
     *
     * @return JournalCollectorInterface
     */
    public function setBudgets(Collection $budgets): JournalCollectorInterface
    {
        $budgetIds = $budgets->pluck('id')->toArray();
        if (count($budgetIds) === 0) {
            return $this;
        }
        $this->joinBudgetTables();
        Log::debug('Journal collector will filter for budgets', $budgetIds);

        $this->query->where(
            function (EloquentBuilder $q) use ($budgetIds) {
                $q->whereIn('budget_transaction.budget_id', $budgetIds);
                $q->orWhereIn('budget_transaction_journal.budget_id', $budgetIds);
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
        if ($page < 1) {
            $page = 1;
        }

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

            return $this;
        }
        Log::debug('The limit is zero, cannot set the page.');

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
            $startStr = $start->format('Y-m-d');
            $endStr   = $end->format('Y-m-d');
            $this->query->where('transaction_journals.date', '>=', $startStr);
            $this->query->where('transaction_journals.date', '<=', $endStr);
            Log::debug(sprintf('JournalCollector range is now %s - %s (inclusive)', $startStr, $endStr));
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
     * @param Collection $tags
     *
     * @return JournalCollectorInterface
     */
    public function setTags(Collection $tags): JournalCollectorInterface
    {
        $this->joinTagTables();
        $tagIds = $tags->pluck('id')->toArray();
        $this->query->whereIn('tag_transaction_journal.tag_id', $tagIds);

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
            Log::debug('Set query collector types', $types);
            $this->query->whereIn('transaction_types.type', $types);
        }

        return $this;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        Log::debug(sprintf('Journal collector now collecting for user #%d', $user->id));
        $this->user = $user;
        $this->startQuery();
    }

    /**
     *
     */
    public function startQuery()
    {
        Log::debug('journalCollector::startQuery');
        /** @var EloquentBuilder $query */
        $query = Transaction::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                            ->leftJoin('transaction_types', 'transaction_types.id', 'transaction_journals.transaction_type_id')
                            ->leftJoin('bills', 'bills.id', 'transaction_journals.bill_id')
                            ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                            ->leftJoin('account_types', 'accounts.account_type_id', 'account_types.id')
                            ->leftJoin('transaction_currencies', 'transaction_currencies.id', 'transactions.transaction_currency_id')
                            ->leftJoin('transaction_currencies as foreign_currencies', 'foreign_currencies.id', 'transactions.foreign_currency_id')
                            ->whereNull('transactions.deleted_at')
                            ->whereNull('transaction_journals.deleted_at')
                            ->where('transaction_journals.user_id', $this->user->id)
                            ->orderBy('transaction_journals.date', 'DESC')
                            ->orderBy('transaction_journals.order', 'ASC')
                            ->orderBy('transaction_journals.id', 'DESC')
                            ->orderBy('transaction_journals.description', 'DESC')
                            ->orderBy('transactions.amount','DESC');

        $this->query = $query;

    }

    /**
     * @return JournalCollectorInterface
     */
    public function withBudgetInformation(): JournalCollectorInterface
    {
        $this->joinBudgetTables();

        return $this;
    }

    /**
     * @return JournalCollectorInterface
     */
    public function withCategoryInformation(): JournalCollectorInterface
    {

        $this->joinCategoryTables();

        return $this;
    }

    /**
     * @return JournalCollectorInterface
     */
    public function withOpposingAccount(): JournalCollectorInterface
    {
        $this->joinOpposingTables();

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
     * @param Collection $set
     *
     * @return Collection
     */
    private function filter(Collection $set): Collection
    {
        // create all possible filters:
        $filters = [
            InternalTransferFilter::class => new InternalTransferFilter($this->accountIds),
            OpposingAccountFilter::class  => new OpposingAccountFilter($this->accountIds),
            TransferFilter::class         => new TransferFilter,
            PositiveAmountFilter::class   => new PositiveAmountFilter,
            NegativeAmountFilter::class   => new NegativeAmountFilter,
        ];
        Log::debug(sprintf('Will run %d filters on the set.', count($this->filters)));
        foreach ($this->filters as $enabled) {
            if (isset($filters[$enabled])) {
                Log::debug(sprintf('Before filter %s: %d', $enabled, $set->count()));
                $set = $filters[$enabled]->filter($set);
                Log::debug(sprintf('After filter %s: %d', $enabled, $set->count()));
            }
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
            $this->query->leftJoin('budgets as transaction_journal_budgets', 'transaction_journal_budgets.id', '=', 'budget_transaction_journal.budget_id');
            $this->query->leftJoin('budget_transaction', 'budget_transaction.transaction_id', '=', 'transactions.id');
            $this->query->leftJoin('budgets as transaction_budgets', 'transaction_budgets.id', '=', 'budget_transaction.budget_id');
            $this->query->whereNull('transaction_journal_budgets.deleted_at');
            $this->query->whereNull('transaction_budgets.deleted_at');

            $this->fields[] = 'budget_transaction_journal.budget_id as transaction_journal_budget_id';
            $this->fields[] = 'transaction_journal_budgets.encrypted as transaction_journal_budget_encrypted';
            $this->fields[] = 'transaction_journal_budgets.name as transaction_journal_budget_name';

            $this->fields[] = 'budget_transaction.budget_id as transaction_budget_id';
            $this->fields[] = 'transaction_budgets.encrypted as transaction_budget_encrypted';
            $this->fields[] = 'transaction_budgets.name as transaction_budget_name';
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
            $this->query->leftJoin(
                'categories as transaction_journal_categories', 'transaction_journal_categories.id', '=', 'category_transaction_journal.category_id'
            );

            $this->query->leftJoin('category_transaction', 'category_transaction.transaction_id', '=', 'transactions.id');
            $this->query->leftJoin('categories as transaction_categories', 'transaction_categories.id', '=', 'category_transaction.category_id');

            $this->fields[] = 'category_transaction_journal.category_id as transaction_journal_category_id';
            $this->fields[] = 'transaction_journal_categories.encrypted as transaction_journal_category_encrypted';
            $this->fields[] = 'transaction_journal_categories.name as transaction_journal_category_name';

            $this->fields[] = 'category_transaction.category_id as transaction_category_id';
            $this->fields[] = 'transaction_categories.encrypted as transaction_category_encrypted';
            $this->fields[] = 'transaction_categories.name as transaction_category_name';
        }
    }

    /**
     *
     */
    private function joinOpposingTables()
    {
        if (!$this->joinedOpposing) {
            Log::debug('joinedOpposing is false');
            // join opposing transaction (hard):
            $this->query->leftJoin(
                'transactions as opposing', function (JoinClause $join) {
                $join->on('opposing.transaction_journal_id', '=', 'transactions.transaction_journal_id')
                     ->where('opposing.identifier', '=', DB::raw('transactions.identifier'))
                     ->where('opposing.amount', '=', DB::raw('transactions.amount * -1'));
            }
            );
            $this->query->leftJoin('accounts as opposing_accounts', 'opposing.account_id', '=', 'opposing_accounts.id');
            $this->query->leftJoin('account_types as opposing_account_types', 'opposing_accounts.account_type_id', '=', 'opposing_account_types.id');
            $this->query->whereNull('opposing.deleted_at');

            $this->fields[] = 'opposing.id as opposing_id';
            $this->fields[] = 'opposing.account_id as opposing_account_id';
            $this->fields[] = 'opposing_accounts.name as opposing_account_name';
            $this->fields[] = 'opposing_accounts.encrypted as opposing_account_encrypted';
            $this->fields[] = 'opposing_accounts.iban as opposing_account_iban';

            $this->fields[]       = 'opposing_account_types.type as opposing_account_type';
            $this->joinedOpposing = true;
            Log::debug('joinedOpposing is now true!');
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
}
