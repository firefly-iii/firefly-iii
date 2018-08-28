<?php
/**
 * TransactionCollector.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Collector;


use Carbon\Carbon;
use DB;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Filter\CountAttachmentsFilter;
use FireflyIII\Helpers\Filter\FilterInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Helpers\Filter\NegativeAmountFilter;
use FireflyIII\Helpers\Filter\OpposingAccountFilter;
use FireflyIII\Helpers\Filter\PositiveAmountFilter;
use FireflyIII\Helpers\Filter\SplitIndicatorFilter;
use FireflyIII\Helpers\Filter\TransactionViewFilter;
use FireflyIII\Helpers\Filter\TransferFilter;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;

/**
 * Class TransactionCollector
 *
 * @codeCoverageIgnore 
 */
class TransactionCollector implements TransactionCollectorInterface
{

    /** @var array */
    private $accountIds = [];
    /** @var int */
    private $count = 0;

    /** @var array */
    private $fields
        = [
            'transaction_journals.id as journal_id',
            'transaction_journals.description',
            'transaction_journals.date',
            'transaction_journals.encrypted',
            'transaction_journals.created_at',
            'transaction_journals.updated_at',
            'transaction_types.type as transaction_type_type',
            'transaction_journals.bill_id',
            'transaction_journals.updated_at',
            'bills.name as bill_name',
            'bills.name_encrypted as bill_name_encrypted',

            'transactions.id as id',
            'transactions.description as transaction_description',
            'transactions.account_id',
            'transactions.reconciled',
            'transactions.identifier',
            'transactions.transaction_journal_id',
            'transactions.amount as transaction_amount',
            'transactions.transaction_currency_id as transaction_currency_id',

            'transaction_currencies.name as transaction_currency_name',
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
    /** @var bool */
    private $ignoreCache = false;
    /** @var bool */
    private $joinedBudget = false;
    /** @var bool */
    private $joinedCategory = false;
    /** @var bool */
    private $joinedOpposing = false;
    /** @var bool */
    private $joinedTag = false;
    /** @var int */
    private $limit;
    /** @var int */
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
     * @return TransactionCollectorInterface
     */
    public function addFilter(string $filter): TransactionCollectorInterface
    {
        $interfaces = class_implements($filter);
        if (\in_array(FilterInterface::class, $interfaces, true) && !\in_array($filter, $this->filters, true)) {
            Log::debug(sprintf('Enabled filter %s', $filter));
            $this->filters[] = $filter;
        }

        return $this;
    }

    /**
     * @param string $amount
     *
     * @return TransactionCollectorInterface
     */
    public function amountIs(string $amount): TransactionCollectorInterface
    {
        $this->query->where(
            function (EloquentBuilder $q) use ($amount) {
                $q->where('transactions.amount', $amount);
                $q->orWhere('transactions.amount', bcmul($amount, '-1'));
            }
        );

        return $this;
    }

    /**
     * @param string $amount
     *
     * @return TransactionCollectorInterface
     */
    public function amountLess(string $amount): TransactionCollectorInterface
    {
        $this->query->where(
            function (EloquentBuilder $q1) use ($amount) {
                $q1->where(
                    function (EloquentBuilder $q2) use ($amount) {
                        // amount < 0 and .amount > -$amount
                        $invertedAmount = bcmul($amount, '-1');
                        $q2->where('transactions.amount', '<', 0)->where('transactions.amount', '>', $invertedAmount);
                    }
                )
                   ->orWhere(
                       function (EloquentBuilder $q3) use ($amount) {
                           // amount > 0 and .amount < $amount
                           $q3->where('transactions.amount', '>', 0)->where('transactions.amount', '<', $amount);
                       }
                   );
            }
        );

        return $this;
    }

    /**
     * @param string $amount
     *
     * @return TransactionCollectorInterface
     */
    public function amountMore(string $amount): TransactionCollectorInterface
    {
        $this->query->where(
            function (EloquentBuilder $q1) use ($amount) {
                $q1->where(
                    function (EloquentBuilder $q2) use ($amount) {
                        // amount < 0 and .amount < -$amount
                        $invertedAmount = bcmul($amount, '-1');
                        $q2->where('transactions.amount', '<', 0)->where('transactions.amount', '<', $invertedAmount);
                    }
                )
                   ->orWhere(
                       function (EloquentBuilder $q3) use ($amount) {
                           // amount > 0 and .amount > $amount
                           $q3->where('transactions.amount', '>', 0)->where('transactions.amount', '>', $amount);
                       }
                   );
            }
        );

        return $this;
    }

    /**
     * @return int
     *
     * @throws FireflyException
     */
    public function count(): int
    {
        if (true === $this->run) {
            throw new FireflyException('Cannot count after run in TransactionCollector.');
        }

        $countQuery = clone $this->query;

        // dont need some fields:
        $countQuery->getQuery()->limit      = null;
        $countQuery->getQuery()->offset     = null;
        $countQuery->getQuery()->unionLimit = null;
        $countQuery->getQuery()->groups     = null;
        $countQuery->getQuery()->orders     = null;
        $countQuery->groupBy('accounts.user_id');
        $this->count = (int)$countQuery->count();

        return $this->count;
    }

    /**
     * @return Collection
     */
    public function getTransactions(): Collection
    {
        $this->run = true;

        // find query set in cache.
        $hash  = hash('sha256', $this->query->toSql() . serialize($this->query->getBindings()));
        $key   = 'query-' . substr($hash, -8);
        $cache = new CacheProperties;
        $cache->addProperty($key);
        foreach ($this->filters as $filter) {
            $cache->addProperty((string)$filter);
        }
        if (false === $this->ignoreCache && $cache->has()) {
            Log::debug(sprintf('Return cache of query with ID "%s".', $key));

            return $cache->get(); // @codeCoverageIgnore

        }
        /** @var Collection $set */
        $set = $this->query->get(array_values($this->fields));

        // run all filters:
        $set = $this->filter($set);

        // loop for decryption.
        $set->each(
            function (Transaction $transaction) {
                $transaction->date        = new Carbon($transaction->date);
                $transaction->description = app('steam')->decrypt((int)$transaction->encrypted, $transaction->description);

                if (null !== $transaction->bill_name) {
                    $transaction->bill_name = app('steam')->decrypt((int)$transaction->bill_name_encrypted, $transaction->bill_name);
                }
                $transaction->account_name          = app('steam')->tryDecrypt($transaction->account_name);
                $transaction->opposing_account_name = app('steam')->tryDecrypt($transaction->opposing_account_name);
                $transaction->account_iban          = app('steam')->tryDecrypt($transaction->account_iban);
                $transaction->opposing_account_iban = app('steam')->tryDecrypt($transaction->opposing_account_iban);

                // budget name
                $transaction->transaction_journal_budget_name = app('steam')->tryDecrypt($transaction->transaction_journal_budget_name);
                $transaction->transaction_budget_name         = app('steam')->tryDecrypt($transaction->transaction_budget_name);
                // category name:
                $transaction->transaction_journal_category_name = app('steam')->tryDecrypt($transaction->transaction_journal_category_name);
                $transaction->transaction_category_name         = app('steam')->tryDecrypt($transaction->transaction_category_name);
            }

        );
        Log::debug(sprintf('Cached query with ID "%s".', $key));
        $cache->store($set);

        return $set;
    }

    /**
     * @return LengthAwarePaginator
     * @throws FireflyException
     */
    public function getPaginatedTransactions(): LengthAwarePaginator
    {
        if (true === $this->run) {
            throw new FireflyException('Cannot getPaginatedTransactions after run in TransactionCollector.');
        }
        $this->count();
        $set      = $this->getTransactions();
        $journals = new LengthAwarePaginator($set, $this->count, $this->limit, $this->page);

        return $journals;
    }

    /**
     * @return EloquentBuilder
     */
    public function getQuery(): EloquentBuilder
    {
        return $this->query;
    }

    /**
     * @return TransactionCollectorInterface
     */
    public function ignoreCache(): TransactionCollectorInterface
    {
        $this->ignoreCache = true;

        return $this;
    }

    /**
     * @param string $filter
     *
     * @return TransactionCollectorInterface
     */
    public function removeFilter(string $filter): TransactionCollectorInterface
    {
        $key = array_search($filter, $this->filters, true);
        if (!(false === $key)) {
            Log::debug(sprintf('Removed filter %s', $filter));
            unset($this->filters[$key]);
        }

        return $this;
    }

    /**
     * @param Collection $accounts
     *
     * @return TransactionCollectorInterface
     */
    public function setAccounts(Collection $accounts): TransactionCollectorInterface
    {
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $this->query->whereIn('transactions.account_id', $accountIds);
            Log::debug(sprintf('setAccounts: %s', implode(', ', $accountIds)));
            $this->accountIds = $accountIds;
        }

        if ($accounts->count() > 1) {
            $this->addFilter(TransferFilter::class);
        }

        return $this;
    }

    /**
     * @param Carbon $after
     *
     * @return TransactionCollectorInterface
     */
    public function setAfter(Carbon $after): TransactionCollectorInterface
    {
        $afterStr = $after->format('Y-m-d 00:00:00');
        $this->query->where('transaction_journals.date', '>=', $afterStr);
        Log::debug(sprintf('TransactionCollector range is now after %s (inclusive)', $afterStr));

        return $this;
    }

    /**
     * @return TransactionCollectorInterface
     */
    public function setAllAssetAccounts(): TransactionCollectorInterface
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
     * @param Carbon $before
     *
     * @return TransactionCollectorInterface
     */
    public function setBefore(Carbon $before): TransactionCollectorInterface
    {
        $beforeStr = $before->format('Y-m-d 00:00:00');
        $this->query->where('transaction_journals.date', '<=', $beforeStr);
        Log::debug(sprintf('TransactionCollector range is now before %s (inclusive)', $beforeStr));

        return $this;
    }

    /**
     * @param Collection $bills
     *
     * @return TransactionCollectorInterface
     */
    public function setBills(Collection $bills): TransactionCollectorInterface
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
     * @return TransactionCollectorInterface
     */
    public function setBudget(Budget $budget): TransactionCollectorInterface
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
     * @return TransactionCollectorInterface
     */
    public function setBudgets(Collection $budgets): TransactionCollectorInterface
    {
        $budgetIds = $budgets->pluck('id')->toArray();
        if (0 !== \count($budgetIds)) {
            $this->joinBudgetTables();
            Log::debug('Journal collector will filter for budgets', $budgetIds);

            $this->query->where(
                function (EloquentBuilder $q) use ($budgetIds) {
                    $q->whereIn('budget_transaction.budget_id', $budgetIds);
                    $q->orWhereIn('budget_transaction_journal.budget_id', $budgetIds);
                }
            );
        }

        return $this;
    }

    /**
     * @param Collection $categories
     *
     * @return TransactionCollectorInterface
     */
    public function setCategories(Collection $categories): TransactionCollectorInterface
    {
        $categoryIds = $categories->pluck('id')->toArray();
        if (0 !== \count($categoryIds)) {
            $this->joinCategoryTables();

            $this->query->where(
                function (EloquentBuilder $q) use ($categoryIds) {
                    $q->whereIn('category_transaction.category_id', $categoryIds);
                    $q->orWhereIn('category_transaction_journal.category_id', $categoryIds);
                }
            );
        }

        return $this;
    }

    /**
     * @param Category $category
     *
     * @return TransactionCollectorInterface
     */
    public function setCategory(Category $category): TransactionCollectorInterface
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
     * @param Collection $journals
     *
     * @return TransactionCollectorInterface
     */
    public function setJournals(Collection $journals): TransactionCollectorInterface
    {
        $ids = $journals->pluck('id')->toArray();
        $this->query->where(
            function (EloquentBuilder $q) use ($ids) {
                $q->whereIn('transaction_journals.id', $ids);
            }
        );

        return $this;
    }

    /**
     * @param int $limit
     *
     * @return TransactionCollectorInterface
     */
    public function setLimit(int $limit): TransactionCollectorInterface
    {
        $this->limit = $limit;
        $this->query->limit($limit);
        Log::debug(sprintf('Set limit to %d', $limit));

        return $this;
    }

    /**
     * @param int $offset
     *
     * @return TransactionCollectorInterface
     */
    public function setOffset(int $offset): TransactionCollectorInterface
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @param Collection $accounts
     *
     * @return TransactionCollectorInterface
     */
    public function setOpposingAccounts(Collection $accounts): TransactionCollectorInterface
    {
        $this->withOpposingAccount();

        $this->query->whereIn('opposing.account_id', $accounts->pluck('id')->toArray());

        return $this;
    }

    /**
     * @param int $page
     *
     * @return TransactionCollectorInterface
     */
    public function setPage(int $page): TransactionCollectorInterface
    {
        if ($page < 1) {
            $page = 1;
        }

        $this->page = $page;

        if ($page > 0) {
            --$page;
        }
        Log::debug(sprintf('Page is %d', $page));

        if (null !== $this->limit) {
            $offset       = ($this->limit * $page);
            $this->offset = $offset;
            $this->query->skip($offset);
            Log::debug(sprintf('Changed offset to %d', $offset));
        }

        return $this;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return TransactionCollectorInterface
     */
    public function setRange(Carbon $start, Carbon $end): TransactionCollectorInterface
    {
        if ($start <= $end) {
            $startStr = $start->format('Y-m-d 00:00:00');
            $endStr   = $end->format('Y-m-d 23:59:59');
            $this->query->where('transaction_journals.date', '>=', $startStr);
            $this->query->where('transaction_journals.date', '<=', $endStr);
            Log::debug(sprintf('TransactionCollector range is now %s - %s (inclusive)', $startStr, $endStr));
        }

        return $this;
    }

    /**
     * @param Tag $tag
     *
     * @return TransactionCollectorInterface
     */
    public function setTag(Tag $tag): TransactionCollectorInterface
    {
        $this->joinTagTables();
        $this->query->where('tag_transaction_journal.tag_id', $tag->id);

        return $this;
    }

    /**
     * @param Collection $tags
     *
     * @return TransactionCollectorInterface
     */
    public function setTags(Collection $tags): TransactionCollectorInterface
    {
        $this->joinTagTables();
        $tagIds = $tags->pluck('id')->toArray();
        $this->query->whereIn('tag_transaction_journal.tag_id', $tagIds);

        return $this;
    }

    /**
     * @param array $types
     *
     * @return TransactionCollectorInterface
     */
    public function setTypes(array $types): TransactionCollectorInterface
    {
        if (\count($types) > 0) {
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
    public function startQuery(): void
    {
        Log::debug('TransactionCollector::startQuery');
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
                            ->orderBy('transactions.identifier', 'ASC')
                            ->orderBy('transactions.amount', 'DESC');

        $this->query = $query;
    }

    /**
     * @return TransactionCollectorInterface
     */
    public function withBudgetInformation(): TransactionCollectorInterface
    {
        $this->joinBudgetTables();

        return $this;
    }

    /**
     * @return TransactionCollectorInterface
     */
    public function withCategoryInformation(): TransactionCollectorInterface
    {
        $this->joinCategoryTables();

        return $this;
    }

    /**
     * @return TransactionCollectorInterface
     */
    public function withOpposingAccount(): TransactionCollectorInterface
    {
        $this->joinOpposingTables();

        return $this;
    }

    /**
     * @return TransactionCollectorInterface
     */
    public function withoutBudget(): TransactionCollectorInterface
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
     * @return TransactionCollectorInterface
     */
    public function withoutCategory(): TransactionCollectorInterface
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
            SplitIndicatorFilter::class   => new SplitIndicatorFilter,
            CountAttachmentsFilter::class => new CountAttachmentsFilter,
            TransactionViewFilter::class  => new TransactionViewFilter,
        ];
        Log::debug(sprintf('Will run %d filters on the set.', \count($this->filters)));
        foreach ($this->filters as $enabled) {
            if (isset($filters[$enabled])) {
                Log::debug(sprintf('Before filter %s: %d', $enabled, $set->count()));
                /** @var Collection $set */
                $set = $filters[$enabled]->filter($set);
                Log::debug(sprintf('After filter %s: %d', $enabled, $set->count()));
            }
        }

        return $set;
    }

    /**
     *
     */
    private function joinBudgetTables(): void
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
    private function joinCategoryTables(): void
    {
        if (!$this->joinedCategory) {
            // join some extra tables:
            $this->joinedCategory = true;
            $this->query->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
            $this->query->leftJoin(
                'categories as transaction_journal_categories',
                'transaction_journal_categories.id',
                '=',
                'category_transaction_journal.category_id'
            );

            $this->query->leftJoin('category_transaction', 'category_transaction.transaction_id', '=', 'transactions.id');
            $this->query->leftJoin('categories as transaction_categories', 'transaction_categories.id', '=', 'category_transaction.category_id');
            $this->query->whereNull('transaction_journal_categories.deleted_at');
            $this->query->whereNull('transaction_categories.deleted_at');
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
    private function joinOpposingTables(): void
    {
        if (!$this->joinedOpposing) {
            Log::debug('joinedOpposing is false');
            // join opposing transaction (hard):
            $this->query->leftJoin(
                'transactions as opposing',
                function (JoinClause $join) {
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
    private function joinTagTables(): void
    {
        if (!$this->joinedTag) {
            // join some extra tables:
            $this->joinedTag = true;
            $this->query->leftJoin('tag_transaction_journal', 'tag_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
        }
    }
}