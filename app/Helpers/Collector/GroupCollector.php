<?php
/**
 * GroupCollector.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
use Exception;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Class GroupCollector
 */
class GroupCollector implements GroupCollectorInterface
{
    /** @var array The accounts to filter on. Asset accounts or liabilities. */
    private $accountIds;
    /** @var array The standard fields to select. */
    private $fields;
    /** @var bool Will be set to true if query result contains account information. (see function withAccountInformation). */
    private $hasAccountInformation;
    /** @var bool Will be true if query result includes bill information. */
    private $hasBillInformation;
    /** @var bool Will be true if query result contains budget info. */
    private $hasBudgetInformation;
    /** @var bool Will be true if query result contains category info. */
    private $hasCatInformation;
    /** @var bool Will be true of the query has the tag info tables joined. */
    private $hasJoinedTagTables;
    /** @var int The maximum number of results. */
    private $limit;
    /** @var int The page to return. */
    private $page;
    /** @var HasMany The query object. */
    private $query;
    /** @var int Total number of results. */
    private $total;
    /** @var User The user object. */
    private $user;

    /**
     * Group collector constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            app('log')->warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
        $this->hasAccountInformation = false;
        $this->hasCatInformation     = false;
        $this->hasBudgetInformation  = false;
        $this->hasBillInformation    = false;
        $this->hasJoinedTagTables    = false;

        $this->total  = 0;
        $this->limit  = 50;
        $this->page   = 0;
        $this->fields = [
            # group
            'transaction_groups.id as transaction_group_id',
            'transaction_groups.user_id as user_id',
            'transaction_groups.created_at as created_at',
            'transaction_groups.updated_at as updated_at',
            'transaction_groups.title as transaction_group_title',

            # journal
            'transaction_journals.id as transaction_journal_id',
            'transaction_journals.transaction_type_id',
            'transaction_types.type as transaction_type_type',
            'transaction_journals.description',
            'transaction_journals.date',
            'transaction_journals.order',

            # source info (always present)
            'source.id as source_transaction_id',
            'source.account_id as source_account_id',
            'source.reconciled',

            # currency info:
            'source.amount as amount',
            'source.transaction_currency_id as currency_id',
            'currency.code as currency_code',
            'currency.symbol as currency_symbol',
            'currency.decimal_places as currency_decimal_places',

            # foreign currency info
            'source.foreign_amount as foreign_amount',
            'source.foreign_currency_id as foreign_currency_id',
            'foreign_currency.code as foreign_currency_code',
            'foreign_currency.symbol as foreign_currency_symbol',
            'foreign_currency.decimal_places as foreign_currency_decimal_places',

            # destination account info (always present)
            #'destination.id as destination_transaction_id', // not interesting.
            'destination.account_id as destination_account_id',
        ];
    }

    /**
     * Return the transaction journals without group information. Is useful in some instances.
     *
     * @return array
     */
    public function getExtractedJournals(): array
    {
        $selection = $this->getGroups();
        $return    = new Collection;
        /** @var array $group */
        foreach ($selection as $group) {
            foreach ($group['transactions'] as $journalId => $journal) {
                $return[$journalId] = $journal;
            }
        }

        return $return;
    }

    /**
     * Return the groups.
     *
     * @return Collection
     */
    public function getGroups(): Collection
    {
        /** @var Collection $result */
        $result = $this->query->get($this->fields);

        // now to parse this into an array.
        $collection  = $this->parseArray($result);
        $this->total = $collection->count();

        // now filter the array according to the page and the
        $offset  = $this->page * $this->limit;
        $limited = $collection->slice($offset, $this->limit);

        return $limited;
    }

    /**
     * Same as getGroups but everything is in a paginator.
     *
     * @return LengthAwarePaginator
     */
    public function getPaginatedGroups(): LengthAwarePaginator
    {
        $set = $this->getGroups();

        return new LengthAwarePaginator($set, $this->total, $this->limit, $this->page);
    }

    /**
     * Define which accounts can be part of the source and destination transactions.
     *
     * @param Collection $accounts
     *
     * @return GroupCollectorInterface
     */
    public function setAccounts(Collection $accounts): GroupCollectorInterface
    {
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $this->query->where(
                function (EloquentBuilder $query) use ($accountIds) {
                    $query->whereIn('source.account_id', $accountIds);
                    $query->orWhereIn('destination.account_id', $accountIds);
                }
            );
            app('log')->debug(sprintf('GroupCollector: setAccounts: %s', implode(', ', $accountIds)));
            $this->accountIds = $accountIds;
        }

        return $this;
    }

    /**
     * Limit the search to a specific bill.
     *
     * @param Bill $bill
     *
     * @return GroupCollectorInterface
     */
    public function setBill(Bill $bill): GroupCollectorInterface
    {
        $this->withBudgetInformation();
        $this->query->where('transaction_journals.bill_id', '=', $bill->id);

        return $this;
    }

    /**
     * Limit the search to a specific budget.
     *
     * @param Budget $budget
     *
     * @return GroupCollectorInterface
     */
    public function setBudget(Budget $budget): GroupCollectorInterface
    {
        $this->withBudgetInformation();
        $this->query->where('budgets.id', $budget->id);

        return $this;
    }

    /**
     * Limit the search to a specific set of budgets.
     *
     * @param Collection $budgets
     *
     * @return GroupCollectorInterface
     */
    public function setBudgets(Collection $budgets): GroupCollectorInterface
    {
        $this->withBudgetInformation();
        $this->query->whereIn('budgets.id', $budgets->pluck('id')->toArray());

        return $this;
    }

    /**
     * Limit the search to a specific category.
     *
     * @param Category $category
     *
     * @return GroupCollectorInterface
     */
    public function setCategory(Category $category): GroupCollectorInterface
    {
        $this->withCategoryInformation();
        $this->query->where('categories.id', $category->id);

        return $this;
    }

    /**
     * Limit results to a specific currency, either foreign or normal one.
     *
     * @param TransactionCurrency $currency
     *
     * @return GroupCollectorInterface
     */
    public function setCurrency(TransactionCurrency $currency): GroupCollectorInterface
    {
        $this->query->where(
            function (EloquentBuilder $q) use ($currency) {
                $q->where('source.transaction_currency_id', $currency->id);
                $q->orWhere('source.foreign_currency_id', $currency->id);
            }
        );

        return $this;
    }

    /**
     * Limit the result to a set of specific journals.
     *
     * @param array $journalIds
     *
     * @return GroupCollectorInterface
     */
    public function setJournalIds(array $journalIds): GroupCollectorInterface
    {
        if (\count($journalIds) > 0) {
            $this->query->whereIn('transaction_journals.id', $journalIds);
        }

        return $this;
    }

    /**
     * Limit the number of returned entries.
     *
     * @param int $limit
     *
     * @return GroupCollectorInterface
     */
    public function setLimit(int $limit): GroupCollectorInterface
    {
        $this->limit = $limit;
        app('log')->debug(sprintf('GroupCollector: The limit is now %d', $limit));

        return $this;
    }

    /**
     * Set the page to get.
     *
     * @param int $page
     *
     * @return GroupCollectorInterface
     */
    public function setPage(int $page): GroupCollectorInterface
    {
        $page       = 0 === $page ? 0 : $page - 1;
        $this->page = $page;
        app('log')->debug(sprintf('GroupCollector: page is now %d (is minus 1)', $page));

        return $this;
    }

    /**
     * Set the start and end time of the results to return.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return GroupCollectorInterface
     */
    public function setRange(Carbon $start, Carbon $end): GroupCollectorInterface
    {
        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }
        $startStr = $start->format('Y-m-d H:i:s');
        $endStr   = $end->format('Y-m-d H:i:s');

        $this->query->where('transaction_journals.date', '>=', $startStr);
        $this->query->where('transaction_journals.date', '<=', $endStr);
        app('log')->debug(sprintf('TransactionCollector range is now %s - %s (inclusive)', $startStr, $endStr));

        return $this;
    }

    /**
     * Limit results to a specific tag.
     *
     * @param Tag $tag
     *
     * @return GroupCollectorInterface
     */
    public function setTag(Tag $tag): GroupCollectorInterface
    {
        $this->joinTagTables();
        $this->query->where('tag_transaction_journal.tag_id', $tag->id);

        return $this;
    }

    /**
     * Limit the search to one specific transaction group.
     *
     * @param TransactionGroup $transactionGroup
     *
     * @return GroupCollectorInterface
     */
    public function setTransactionGroup(TransactionGroup $transactionGroup): GroupCollectorInterface
    {
        $this->query->where('transaction_groups.id', $transactionGroup->id);

        return $this;
    }

    /**
     * Limit the included transaction types.
     *
     * @param array $types
     *
     * @return GroupCollectorInterface
     */
    public function setTypes(array $types): GroupCollectorInterface
    {
        $this->query->whereIn('transaction_types.type', $types);

        return $this;
    }

    /**
     * Set the user object and start the query.
     *
     * @param User $user
     *
     * @return GroupCollectorInterface
     */
    public function setUser(User $user): GroupCollectorInterface
    {
        $this->user = $user;
        $this->startQuery();

        return $this;
    }

    /**
     * Automatically include all stuff required to make API calls work.
     *
     * @return GroupCollectorInterface
     */
    public function withAPIInformation(): GroupCollectorInterface
    {
        // include source + destination account name and type.
        $this->withAccountInformation()
            // include category ID + name (if any)
             ->withCategoryInformation()
            // include budget ID + name (if any)
             ->withBudgetInformation()
            // include bill ID + name (if any)
             ->withBillInformation();

        return $this;
    }

    /**
     * Will include the source and destination account names and types.
     *
     * @return GroupCollectorInterface
     */
    public function withAccountInformation(): GroupCollectorInterface
    {
        if (false === $this->hasAccountInformation) {
            // join source account table
            $this->query->leftJoin('accounts as source_account', 'source_account.id', '=', 'source.account_id');
            // join source account type table
            $this->query->leftJoin('account_types as source_account_type', 'source_account_type.id', '=', 'source_account.account_type_id');

            // add source account fields:
            $this->fields[] = 'source_account.name as source_account_name';
            $this->fields[] = 'source_account.iban as source_account_iban';
            $this->fields[] = 'source_account_type.type as source_account_type';

            // same for dest
            $this->query->leftJoin('accounts as dest_account', 'dest_account.id', '=', 'destination.account_id');
            $this->query->leftJoin('account_types as dest_account_type', 'dest_account_type.id', '=', 'dest_account.account_type_id');

            // and add fields:
            $this->fields[] = 'dest_account.name as destination_account_name';
            $this->fields[] = 'dest_account.iban as destination_account_iban';
            $this->fields[] = 'dest_account_type.type as destination_account_type';


            $this->hasAccountInformation = true;
        }

        return $this;
    }

    /**
     * Will include bill name + ID, if any.
     *
     * @return GroupCollectorInterface
     */
    public function withBillInformation(): GroupCollectorInterface
    {
        if (false === $this->hasBillInformation) {
            // join bill table
            $this->query->leftJoin('bills', 'bills.id', '=', 'transaction_journals.bill_id');
            // add fields
            $this->fields[]           = 'bills.id as bill_id';
            $this->fields[]           = 'bills.name as bill_name';
            $this->hasBillInformation = true;
        }

        return $this;
    }

    /**
     * Will include budget ID + name, if any.
     *
     * @return GroupCollectorInterface
     */
    public function withBudgetInformation(): GroupCollectorInterface
    {
        if (false === $this->hasBudgetInformation) {
            // join link table
            $this->query->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
            // join cat table
            $this->query->leftJoin('budgets', 'budget_transaction_journal.budget_id', '=', 'budgets.id');
            // add fields
            $this->fields[]             = 'budgets.id as budget_id';
            $this->fields[]             = 'budgets.name as budget_name';
            $this->hasBudgetInformation = true;
        }

        return $this;
    }

    /**
     * Will include category ID + name, if any.
     *
     * @return GroupCollectorInterface
     */
    public function withCategoryInformation(): GroupCollectorInterface
    {
        if (false === $this->hasCatInformation) {
            // join link table
            $this->query->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
            // join cat table
            $this->query->leftJoin('categories', 'category_transaction_journal.category_id', '=', 'categories.id');
            // add fields
            $this->fields[]          = 'categories.id as category_id';
            $this->fields[]          = 'categories.name as category_name';
            $this->hasCatInformation = true;
        }

        return $this;
    }

    /**
     * Join table to get tag information.
     */
    private function joinTagTables(): void
    {
        if (false === $this->hasJoinedTagTables) {
            // join some extra tables:
            $this->hasJoinedTagTables = true;
            $this->query->leftJoin('tag_transaction_journal', 'tag_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
        }
    }

    /**
     * @param Collection $collection
     *
     * @return Collection
     * @throws Exception
     */
    private function parseArray(Collection $collection): Collection
    {
        $groups = [];
        /** @var TransactionGroup $augmentedGroup */
        foreach ($collection as $augmentedGroup) {
            $groupId = $augmentedGroup->transaction_group_id;
            if (!isset($groups[$groupId])) {
                // make new array
                $groupArray                             = [
                    'id'           => $augmentedGroup->transaction_group_id,
                    'user_id'      => $augmentedGroup->user_id,
                    'title'        => $augmentedGroup->title,
                    'count'        => 1,
                    'sum'          => $augmentedGroup->amount,
                    'foreign_sum'  => $augmentedGroup->foreign_amount ?? '0',
                    'transactions' => [],
                ];
                $journalId                              = (int)$augmentedGroup->transaction_journal_id;
                $groupArray['transactions'][$journalId] = $this->parseAugmentedGroup($augmentedGroup);
                $groups[$groupId]                       = $groupArray;
                continue;
            }
            $groups[$groupId]['count']++;
            $groups[$groupId]['sum']                      = bcadd($augmentedGroup->amount, $groups[$groupId]['sum']);
            $groups[$groupId]['foreign_sum']              = bcadd($augmentedGroup->foreign_amount ?? '0', $groups[$groupId]['foreign_sum']);
            $journalId                                    = (int)$augmentedGroup->transaction_journal_id;
            $groups[$groupId]['transactions'][$journalId] = $this->parseAugmentedGroup($augmentedGroup);
        }

        return new Collection($groups);
    }

    /**
     * @param TransactionGroup $augmentedGroup
     *
     * @return array
     * @throws Exception
     */
    private function parseAugmentedGroup(TransactionGroup $augmentedGroup): array
    {
        $result               = $augmentedGroup->toArray();
        $result['date']       = new Carbon($result['date']);
        $result['created_at'] = new Carbon($result['created_at']);
        $result['updated_at'] = new Carbon($result['updated_at']);
        $result['reconciled'] = 1 === (int)$result['reconciled'];

        return $result;
    }

    /**
     * Build the query.
     */
    private function startQuery(): void
    {
        app('log')->debug('TransactionCollector::startQuery');
        $this->query = $this->user
            ->transactionGroups()
            ->leftJoin('transaction_journals', 'transaction_journals.transaction_group_id', 'transaction_groups.id')
            // join source transaction.
            ->leftJoin(
                'transactions as source', function (JoinClause $join) {
                $join->on('source.transaction_journal_id', '=', 'transaction_journals.id')
                     ->where('source.amount', '<', 0);
            }
            )
            // join destination transaction
            ->leftJoin(
                'transactions as destination', function (JoinClause $join) {
                $join->on('destination.transaction_journal_id', '=', 'transaction_journals.id')
                     ->where('destination.amount', '>', 0);
            }
            )
            // left join transaction type.
            ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->leftJoin('transaction_currencies as currency', 'currency.id', '=', 'source.transaction_currency_id')
            ->leftJoin('transaction_currencies as foreign_currency', 'foreign_currency.id', '=', 'source.foreign_currency_id')
            ->whereNull('transaction_groups.deleted_at')
            ->whereNull('transaction_journals.deleted_at')
            ->whereNull('source.deleted_at')
            ->whereNull('destination.deleted_at')
            ->orderBy('transaction_journals.date', 'DESC')
            ->orderBy('transaction_journals.order', 'ASC')
            ->orderBy('transaction_journals.id', 'DESC')
            ->orderBy('transaction_journals.description', 'DESC')
            ->orderBy('source.amount', 'DESC');
    }
}