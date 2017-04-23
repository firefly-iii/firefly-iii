<?php
/**
 * JournalCollector.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

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
use Illuminate\Contracts\Encryption\DecryptException;
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
            'transaction_currencies.code as transaction_currency_code',
            'transaction_types.type as transaction_type_type',
            'transaction_journals.bill_id',
            'bills.name as bill_name',
            'bills.name_encrypted as bill_name_encrypted',
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
    private $filterInternalTransfers;
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
     * @return JournalCollectorInterface
     */
    public function disableInternalFilter(): JournalCollectorInterface
    {
        $this->filterInternalTransfers = false;

        return $this;
    }

    /**
     * @return JournalCollectorInterface
     */
    public function enableInternalFilter(): JournalCollectorInterface
    {
        $this->filterInternalTransfers = true;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getJournals(): Collection
    {
        $this->run = true;
        /** @var Collection $set */
        $set = $this->query->get(array_values($this->fields));
        Log::debug(sprintf('Count of set is %d', $set->count()));
        $set = $this->filterTransfers($set);
        Log::debug(sprintf('Count of set after filterTransfers() is %d', $set->count()));

        // possibly filter "internal" transfers:
        $set = $this->filterInternalTransfers($set);
        Log::debug(sprintf('Count of set after filterInternalTransfers() is %d', $set->count()));


        // loop for decryption.
        $set->each(
            function (Transaction $transaction) {
                $transaction->date        = new Carbon($transaction->date);
                $transaction->description = Steam::decrypt(intval($transaction->encrypted), $transaction->description);

                if (!is_null($transaction->bill_name)) {
                    $transaction->bill_name = Steam::decrypt(intval($transaction->bill_name_encrypted), $transaction->bill_name);
                }

                try {
                    $transaction->opposing_account_name = Crypt::decrypt($transaction->opposing_account_name);
                } catch (DecryptException $e) {
                    // if this fails its already decrypted.
                }

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
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser($this->user);
        $accounts = $repository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);
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
        $this->user = $user;
    }

    /**
     *
     */
    public function startQuery()
    {
        /** @var EloquentBuilder $query */
        $query = Transaction::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
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
    private function filterInternalTransfers(Collection $set): Collection
    {
        if ($this->filterInternalTransfers === false) {
            Log::debug('Did NO filtering for internal transfers on given set.');

            return $set;
        }
        if ($this->joinedOpposing === false) {
            Log::info('Cannot filter internal transfers because no opposing information is present.');

            return $set;
        }

        $accountIds = $this->accountIds;
        $set        = $set->filter(
            function (Transaction $transaction) use ($accountIds) {
                // both id's in $accountids?
                if (in_array($transaction->account_id, $accountIds) && in_array($transaction->opposing_account_id, $accountIds)) {
                    Log::debug(
                        sprintf(
                            'Transaction #%d has #%d and #%d in set, so removed',
                            $transaction->id, $transaction->account_id, $transaction->opposing_account_id
                        ), $accountIds
                    );

                    return false;
                }

                return $transaction;

            }
        );

        return $set;
    }

    /**
     * If the set of accounts used by the collector includes more than one asset
     * account, chances are the set include double entries: transfers get selected
     * on both the source, and then again on the destination account.
     *
     * This method filters them out by removing transfers that have been selected twice.
     *
     * @param Collection $set
     *
     * @return Collection
     */
    private function filterTransfers(Collection $set): Collection
    {
        if ($this->filterTransfers) {
            $count = [];
            $new   = new Collection;
            /** @var Transaction $transaction */
            foreach ($set as $transaction) {
                if ($transaction->transaction_type_type !== TransactionType::TRANSFER) {
                    $new->push($transaction);
                    continue;
                }
                // make property string:
                $journalId  = $transaction->transaction_journal_id;
                $amount     = Steam::positive($transaction->transaction_amount);
                $accountIds = [intval($transaction->account_id), intval($transaction->opposing_account_id)];
                sort($accountIds);
                $key = $journalId . '-' . join(',', $accountIds) . '-' . $amount;
                Log::debug(sprintf('Key is %s', $key));
                if (!isset($count[$key])) {
                    // not yet counted? add to new set and count it:
                    $new->push($transaction);
                    $count[$key] = 1;
                }
            }

            return $new;
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

            $this->fields[]       = 'opposing.account_id as opposing_account_id';
            $this->fields[]       = 'opposing_accounts.name as opposing_account_name';
            $this->fields[]       = 'opposing_accounts.encrypted as opposing_account_encrypted';
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
