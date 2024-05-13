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

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountBalance;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Facades\Log;

class AccountBalanceCalculator
{
    public static function recalculate(?Account $account, ?TransactionJournal $transactionJournal): void
    {
        // first collect normal amounts (in whatever currency), and set them.

        // select account_id, transaction_currency_id, foreign_currency_id, sum(amount), sum(foreign_amount) from transactions group by account_id, transaction_currency_id, foreign_currency_id
        $query  = Transaction::groupBy(['transactions.account_id', 'transactions.transaction_currency_id', 'transactions.foreign_currency_id']);
        $title  = 'balance';
        if (null !== $account) {
            $query->where('transactions.account_id', $account->id);
        }
        if (null !== $transactionJournal) {
            $query->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id');
            $query->where('transaction_journals.date', '<=', $transactionJournal->date);
            $title = 'balance_after_journal';
        }

        $result = $query->get(['transactions.account_id', 'transactions.transaction_currency_id', 'transactions.foreign_currency_id', \DB::raw('SUM(transactions.amount) as sum_amount'), \DB::raw('SUM(transactions.foreign_amount) as sum_foreign_amount')]);

        // reset account balances:
        self::resetAccountBalances($title, $account, $transactionJournal);

        /** @var \stdClass $row */
        foreach ($result as $row) {
            $account                       = (int) $row->account_id;
            $transactionCurrency           = (int) $row->transaction_currency_id;
            $foreignCurrency               = (int) $row->foreign_currency_id;
            $sumAmount                     = $row->sum_amount;
            $sumForeignAmount              = $row->sum_foreign_amount;

            // first create for normal currency:
            $entry                         = self::getBalance($title, $account, $transactionCurrency, $transactionJournal?->id);
            $entry->balance                = bcadd($entry->balance, $sumAmount);
            $entry->transaction_journal_id = $transactionJournal?->id;
            $entry->save();
            Log::debug(sprintf('Set balance entry "%s" #%d to amount %s', $title, $entry->id, $entry->balance));

            // then do foreign amount, if present:
            if ($foreignCurrency > 0) {
                $entry                         = self::getBalance($title, $account, $foreignCurrency, $transactionJournal?->id);
                $entry->balance                = bcadd($entry->balance, $sumForeignAmount);
                $entry->transaction_journal_id = $transactionJournal?->id;
                $entry->save();
                Log::debug(sprintf('Set balance entry "%s" #%d to amount %s', $title, $entry->id, $entry->balance));
            }
        }
    }

    private static function getBalance(string $title, int $account, int $currency, ?int $journal): AccountBalance
    {
        $query                          = AccountBalance::where('title', $title)->where('account_id', $account)->where('transaction_currency_id', $currency);

        if (null !== $journal) {
            $query->where('transaction_journal_id', $journal);
        }

        $entry                          = $query->first();
        if (null !== $entry) {
            Log::debug(sprintf('Found account balance "%s" for account #%d and currency #%d: %s', $title, $account, $currency, $entry->balance));

            return $entry;
        }
        $entry                          = new AccountBalance();
        $entry->title                   = $title;
        $entry->account_id              = $account;
        $entry->transaction_currency_id = $currency;
        $entry->transaction_journal_id  = $journal;
        $entry->balance                 = '0';
        $entry->save();
        Log::debug(sprintf('Created new account balance for account #%d and currency #%d: %s', $account, $currency, $entry->balance));

        return $entry;
    }

    private static function resetAccountBalances(string $title, ?Account $account, ?TransactionJournal $transactionJournal): void
    {
        if (null === $account && null === $transactionJournal) {
            AccountBalance::whereNotNull('updated_at')->where('title', $title)->update(['balance' => '0']);
            Log::debug('Set ALL balances to zero.');

            return;
        }
        if (null !== $account && null === $transactionJournal) {
            AccountBalance::where('account_id', $account->id)->where('title', $title)->update(['balance' => '0']);
            Log::debug(sprintf('Set balances of account #%d to zero.', $account->id));

            return;
        }
        AccountBalance::where('account_id', $account->id)->where('transaction_journal_id', $transactionJournal->id)->where('title', $title)->update(['balance' => '0']);
        Log::debug(sprintf('Set balances of account #%d + journal #%d to zero.', $account->id, $transactionJournal->id));
    }

    public static function recalculateByJournal(TransactionJournal $transactionJournal): void
    {
        Log::debug(sprintf('Recalculate balance after journal #%d', $transactionJournal->id));
        // update both account balances, but limit to this transaction or earlier.
        foreach ($transactionJournal->transactions as $transaction) {
            self::recalculate($transaction->account, $transactionJournal);
        }
    }
}
