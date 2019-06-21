<?php
/**
 * GroupSumCollector.php
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Transaction;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class GroupSumCollector
 * @codeCoverageIgnore
 */
class GroupSumCollector implements GroupSumCollectorInterface
{
    /** @var array The fields to select. */
    private $fields;
    /** @var bool Will be true if query has joined transaction type table. */
    private $hasJoinedTypeTable;
    /** @var HasMany The query object. */
    private $query;
    /** @var User The user object. */
    private $user;

    /**
     * GroupSumCollector constructor.
     */
    public function __construct()
    {
        throw new FireflyException('I dont work. dont use me');
        $this->hasJoinedTypeTable = false;
        $this->fields             = [
            'transactions.amount',
            'transactions.transaction_currency_id as currency_id',
            'local.code as currency_code',
            'local.name as currency_name',
            'local.symbol as currency_symbol',
            'local.decimal_places as currency_decimal_places',
            'transactions.foreign_amount',
            'transactions.foreign_currency_id',
            'foreign.code as foreign_currency_code',
            'foreign.name as foreign_currency_name',
            'foreign.symbol as foreign_currency_symbol',
            'foreign.decimal_places as foreign_currency_decimal_places',
        ];
    }

    /**
     * @return array
     */
    public function getSum(): array
    {
        $result = $this->query->get($this->fields);
        $return = [
            'count' => 0,
            'sums'  => [],
        ];
        if (0 === $result->count()) {
            return $return;
        }

        foreach ($result as $row) {
            $return['count']++;
            $currencyId = (int)$row->currency_id;
            if (!isset($return['sums'][$currencyId])) {
                $return['sums'][$currencyId] = [
                    'sum'                     => '0',
                    'currency_id'             => $currencyId,
                    'currency_code'           => $row->currency_code,
                    'currency_symbol'         => $row->currency_symbol,
                    'currency_name'           => $row->currency_name,
                    'currency_decimal_places' => (int)$row->currency_decimal_places,
                ];
            }
            // add amounts:
            $return['sums'][$currencyId]['sum'] = bcadd($return['sums'][$currencyId]['sum'], (string)$row->amount);

            // same but for foreign amounts:
            if (null !== $row->foreign_currency_id) {
                $foreignCurrencyId                         = (int)$row->foreign_currency_id;
                $return['sums'][$foreignCurrencyId]        = [
                    'sum'                     => '0',
                    'currency_id'             => $foreignCurrencyId,
                    'currency_code'           => $row->foreign_currency_code,
                    'currency_symbol'         => $row->foreign_currency_symbol,
                    'currency_name'           => $row->foreign_currency_name,
                    'currency_decimal_places' => (int)$row->foreign_currency_decimal_places,
                ];
                $return['sums'][$foreignCurrencyId]['sum'] = bcadd($return['sums'][$foreignCurrencyId]['sum'], (string)$row->foreign_amount);
            }
        }

        return $return;
    }

    /**
     * Reset the query.
     *
     * @return GroupSumCollectorInterface
     */
    public function resetQuery(): GroupSumCollectorInterface
    {
        $this->startQuery();
        $this->hasJoinedTypeTable = false;

        return $this;
    }

    /**
     * Limit the sum to a set of transaction types.
     *
     * @param array $types
     *
     * @return GroupSumCollectorInterface
     */
    public function setTypes(array $types): GroupSumCollectorInterface
    {
        if (false === $this->hasJoinedTypeTable) {
            $this->joinTypeTable();
        }
        $this->query->whereIn('transaction_types.type', $types);

        return $this;
    }

    /**
     * Set the user object and start the query.
     *
     * @param User $user
     *
     * @return GroupSumCollectorInterface
     */
    public function setUser(User $user): GroupSumCollectorInterface
    {
        $this->user = $user;
        $this->startQuery();

        return $this;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return GroupSumCollectorInterface
     */
    public function setRange(Carbon $start, Carbon $end): GroupSumCollectorInterface
    {
        $this->query
            ->where('transaction_journals.date', '>=', $start->format('Y-m-d H:i:s'))
            ->where('transaction_journals.date', '<=', $end->format('Y-m-d H:i:s'));

        return $this;
    }

    private function joinTypeTable(): void
    {
        $this->hasJoinedTypeTable = true;
        $this->query->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id');
    }

    /**
     *
     */
    private function startQuery(): void
    {
        $this->query = Transaction::
        leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                  ->leftJoin('transaction_currencies as local', 'local.id', '=', 'transactions.transaction_currency_id')
                                  ->leftJoin('transaction_currencies as foreign', 'foreign.id', '=', 'transactions.foreign_currency_id')
                                  ->where('transaction_journals.user_id', $this->user->id)
                                  ->whereNull('transaction_journals.deleted_at')
                                  ->whereNull('transactions.deleted_at')
                                  ->where('transactions.amount', '>', 0);
    }
}