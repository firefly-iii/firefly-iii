<?php
/*
 * Balance.php
 * Copyright (c) 2024 james@firefly-iii.org.
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Support;

use Carbon\Carbon;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class Balance
{
    /**
     * Returns the accounts balances as an array, on the account ID.
     */
    public function getAccountBalances(Collection $accounts, Carbon $date): array
    {
        Log::debug(sprintf('getAccountBalances(<collection>, "%s")', $date->format('Y-m-d')));
        $return     = [];
        $currencies = [];
        $cache      = new CacheProperties();
        $cache->addProperty($accounts->pluck('id')->toArray());
        $cache->addProperty('getAccountBalances');
        $cache->addProperty($date);
        if ($cache->has()) {
            return $cache->get();
        }

        $query      = Transaction::whereIn('transactions.account_id', $accounts->pluck('id')->toArray())
            ->leftJoin('transaction_journals', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->orderBy('transaction_journals.date', 'desc')
            ->orderBy('transaction_journals.order', 'asc')
            ->orderBy('transaction_journals.description', 'desc')
            ->orderBy('transactions.amount', 'desc')
            ->where('transaction_journals.date', '<=', $date)
        ;

        $result     = $query->get(['transactions.account_id', 'transactions.transaction_currency_id', 'transactions.balance_after']);
        foreach ($result as $entry) {
            $accountId                       = (int) $entry->account_id;
            $currencyId                      = (int) $entry->transaction_currency_id;
            $currencies[$currencyId] ??= TransactionCurrency::find($currencyId);
            $return[$accountId]      ??= [];
            if (array_key_exists($currencyId, $return[$accountId])) {
                continue;
            }
            $return[$accountId][$currencyId] = ['currency' => $currencies[$currencyId], 'balance' => $entry->balance_after, 'date' => clone $date];
        }

        return $return;
    }
}
