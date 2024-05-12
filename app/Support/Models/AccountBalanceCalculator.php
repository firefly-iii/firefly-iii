<?php
/*
 * AccountBalanceCalculator.php
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

namespace FireflyIII\Support\Models;

use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountBalance;
use FireflyIII\Models\Transaction;
use Illuminate\Support\Facades\Log;
use stdClass;

class AccountBalanceCalculator
{
    public static function recalculate(?Account $account): void
    {
        // first collect normal amounts (in whatever currency), and set them.

        // select account_id, transaction_currency_id, foreign_currency_id, sum(amount), sum(foreign_amount) from transactions group by account_id, transaction_currency_id, foreign_currency_id
        $result = Transaction
            ::groupBy(['account_id', 'transaction_currency_id', 'foreign_currency_id'])
            ->get(['account_id', 'transaction_currency_id', 'foreign_currency_id', DB::raw('SUM(amount) as sum_amount'), DB::raw('SUM(foreign_amount) as sum_foreign_amount')]);

        // reset account balances:
        self::resetAccountBalances($account);

        /** @var stdClass $row */
        foreach ($result as $row) {
            $account             = (int) $row->account_id;
            $transactionCurrency = (int) $row->transaction_currency_id;
            $foreignCurrency     = (int) $row->foreign_currency_id;
            $sumAmount           = $row->sum_amount;
            $sumForeignAmount    = $row->sum_foreign_amount;

            // first create for normal currency:
            $entry          = self::getBalance('balance', $account, $transactionCurrency);
            $entry->balance = bcadd($entry->balance, $sumAmount);
            $entry->save();
            Log::debug(sprintf('Set balance entry #%d to amount %s', $entry->id, $entry->balance));

            // then do foreign amount, if present:
            if ($foreignCurrency > 0) {
                $entry = self::getBalance('balance', $account, $foreignCurrency);
                $entry->balance = bcadd($entry->balance, $sumForeignAmount);
                $entry->save();
                Log::debug(sprintf('Set balance entry #%d to amount %s', $entry->id, $entry->balance));
            }
        }
        return;
    }
    private static function getBalance(string $title, int $account, int $currency): AccountBalance
    {
        $entry = AccountBalance::where('title', $title)->where('account_id', $account)->where('transaction_currency_id', $currency)->first();
        if (null !== $entry) {
            Log::debug(sprintf('Found account balance for account #%d and currency #%d: %s', $account, $currency, $entry->balance));
            return $entry;
        }
        $entry                          = new AccountBalance;
        $entry->title                   = $title;
        $entry->account_id              = $account;
        $entry->transaction_currency_id = $currency;
        $entry->balance                 = '0';
        $entry->save();
        Log::debug(sprintf('Created new account balance for account #%d and currency #%d: %s', $account, $currency, $entry->balance));
        return $entry;
    }

    private static function resetAccountBalances(?Account $account): void
    {
        if (null === $account) {
            AccountBalance::whereNotNull('updated_at')->update(['balance' => '0']);
            Log::debug('Set ALL balances to zero.');
            return;
        }
        AccountBalance::where('account_id', $account->id)->update(['balance' => '0']);
        Log::debug(sprintf('Set balances of account #%d to zero.', $account->id));
    }


}
