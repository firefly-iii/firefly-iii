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
use FireflyIII\Models\TransactionGroup;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
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
    /** @var int The maximum number of results. */
    private $limit;
    /** @var int The page to return. */
    private $page;
    /** @var HasMany The query object. */
    private $query;
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
        $this->limit  = 50;
        $this->page   = 0;
        $this->fields = [
            'transaction_groups.id as transaction_group_id',
            'transaction_groups.title as transaction_group_title',
            'transaction_journals.id as transaction_journal_id',
            'transaction_journals.transaction_type_id',
            'transaction_types.type as transaction_type_type',
            'transaction_journals.bill_id',
            'transaction_journals.description',
            'transaction_journals.date',
            'source.id as source_transaction_id',
            'source.account_id as source_account_id',

            # currency info:
            'source.amount as amount',
            'source.transaction_currency_id as transaction_currency_id',
            'currency.decimal_places as currency_decimal_places',
            'currency.symbol as currency_symbol',

            # foreign currency info
            'source.foreign_amount as foreign_amount',
            'source.foreign_currency_id as foreign_currency_id',
            'foreign_currency.decimal_places as foreign_currency_decimal_places',
            'foreign_currency.symbol as foreign_currency_symbol',

            # destination account info:
            'destination.account_id as destination_account_id',
        ];
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
        $array = $this->parseArray($result);

        // now filter the array according to the page and the
        $offset  = $this->page * $this->limit;
        $limited = $array->slice($offset, $this->limit);

        return $limited;
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
     * @param Collection $collection
     *
     * @return Collection
     */
    private function parseArray(Collection $collection): Collection
    {
        $groups = [];
        /** @var TransactionGroup $augumentedGroup */
        foreach ($collection as $augumentedGroup) {
            $groupId = $augumentedGroup->transaction_group_id;
            if (!isset($groups[$groupId])) {
                // make new array
                $groupArray       = [
                    'id'           => $augumentedGroup->id,
                    'title'        => $augumentedGroup->title,
                    'count'        => 1,
                    'sum'          => $augumentedGroup->amount,
                    'foreign_sum'  => $augumentedGroup->foreign_amount ?? '0',
                    'transactions' => [$this->parseAugumentedGroup($augumentedGroup)],
                ];
                $groups[$groupId] = $groupArray;
                continue;
            }
            $groups[$groupId]['count']++;
            $groups[$groupId]['sum']            = bcadd($augumentedGroup->amount, $groups[$groupId]['sum']);
            $groups[$groupId]['foreign_sum']    = bcadd($augumentedGroup->foreign_amount ?? '0', $groups[$groupId]['foreign_sum']);
            $groups[$groupId]['transactions'][] = $this->parseAugumentedGroup($augumentedGroup);
        }

        return new Collection($groups);
    }

    /**
     * @param TransactionGroup $augumentedGroup
     *
     * @throws Exception
     * @return array
     */
    private function parseAugumentedGroup(TransactionGroup $augumentedGroup): array
    {
        $result         = $augumentedGroup->toArray();
        $result['date'] = new Carbon($result['date']);

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
            ->leftJoin('transaction_currencies as currency','currency.id','=','source.transaction_currency_id')
            ->leftJoin('transaction_currencies as foreign_currency','foreign_currency.id','=','source.foreign_currency_id')
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