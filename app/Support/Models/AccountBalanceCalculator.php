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
use stdClass;

class AccountBalanceCalculator
{

    /**
     * Recalculate all balances for a given account.
     *
     * Je moet toch altijd wel alles doen want je weet niet waar een transaction journal invloed op heeft.
     * Dus dit aantikken per transaction journal is zinloos, beide accounts moeten gedaan worden.
     *
     *
     * @param Account $account
     *
     * @return void
     */
    public static function recalculateForAccount(Account $account): void
    {
        self::recalculate($account);
    }

    public static function recalculateForTransactionJournal(TransactionJournal $transactionJournal): void
    {
        foreach ($transactionJournal->transactions as $transaction) {
            self::recalculateForAccount($transaction->account);
        }
    }

    /**
     * select account_id, transaction_currency_id, foreign_currency_id, sum(amount), sum(foreign_amount) from
     * transactions group by account_id, transaction_currency_id, foreign_currency_id
     *
     * @param Account|null $account
     *
     * @return void
     */
    public static function recalculate(?Account $account): void
    {
        self::recalculateLatest($account);
        // loop all transaction journals and set those amounts too.
        self::recalculateJournals($account);

        // loop all dates and set those amounts too.

    }

    private static function getAccountBalanceByAccount(int $account, int $currency): AccountBalance
    {
        $query = AccountBalance::where('title', 'balance')->where('account_id', $account)->where('transaction_currency_id', $currency);

        $entry = $query->first();
        if (null !== $entry) {
            //Log::debug(sprintf('Found account balance "balance" for account #%d and currency #%d: %s', $account, $currency, $entry->balance));

            return $entry;
        }
        $entry                          = new AccountBalance();
        $entry->title                   = 'balance';
        $entry->account_id              = $account;
        $entry->transaction_currency_id = $currency;
        $entry->balance                 = '0';
        $entry->save();
        //Log::debug(sprintf('Created new account balance for account #%d and currency #%d: %s', $account, $currency, $entry->balance));

        return $entry;
    }

    private static function getAccountBalanceByJournal(string $title, int $account, int $journal, int $currency): AccountBalance
    {
        $query = AccountBalance::where('title', $title)->where('account_id', $account)->where('transaction_journal_id', $journal)->where('transaction_currency_id', $currency);

        $entry = $query->first();
        if (null !== $entry) {
            return $entry;
        }
        $entry                          = new AccountBalance();
        $entry->title                   = $title;
        $entry->account_id              = $account;
        $entry->transaction_journal_id  = $journal;
        $entry->transaction_currency_id = $currency;
        $entry->balance                 = '0';
        $entry->save();

        return $entry;
    }

    private static function recalculateLatest(?Account $account): void
    {
        $query = Transaction::groupBy(['transactions.account_id', 'transactions.transaction_currency_id', 'transactions.foreign_currency_id']);

        if (null !== $account) {
            $query->where('transactions.account_id', $account->id);
        }
        $result = $query->get(['transactions.account_id', 'transactions.transaction_currency_id', 'transactions.foreign_currency_id', \DB::raw('SUM(transactions.amount) as sum_amount'), \DB::raw('SUM(transactions.foreign_amount) as sum_foreign_amount')]);

        // reset account balances:
        self::resetAccountBalancesByAccount('balance', $account);

        /** @var stdClass $row */
        foreach ($result as $row) {
            $account             = (int) $row->account_id;
            $transactionCurrency = (int) $row->transaction_currency_id;
            $foreignCurrency     = (int) $row->foreign_currency_id;
            $sumAmount           = $row->sum_amount;
            $sumForeignAmount    = $row->sum_foreign_amount;

            // first create for normal currency:
            $entry          = self::getAccountBalanceByAccount($account, $transactionCurrency);
            $entry->balance = bcadd($entry->balance, $sumAmount);
            $entry->save();
//            Log::debug(sprintf('Set balance entry  #%d ("balance") to amount %s', $entry->id, $entry->balance));

            // then do foreign amount, if present:
            if ($foreignCurrency > 0) {
                $entry          = self::getAccountBalanceByAccount($account, $foreignCurrency);
                $entry->balance = bcadd($entry->balance, $sumForeignAmount);
                $entry->save();
//                Log::debug(sprintf('Set balance entry  #%d ("balance") to amount %s', $entry->id, $entry->balance));
            }
        }
    }

    private static function resetAccountBalancesByAccount(string $title, ?Account $account): void
    {
        if (null === $account) {
            AccountBalance::whereNotNull('updated_at')->where('title', $title)->update(['balance' => '0']);
            Log::debug('Set ALL balances to zero.');

            return;
        }
        AccountBalance::where('account_id', $account->id)->where('title', $title)->update(['balance' => '0']);
        Log::debug(sprintf('Set balances of account #%d to zero.', $account->id));
    }

    public static function recalculateByJournal(TransactionJournal $transactionJournal): void
    {
        Log::debug(sprintf('Recalculate balance after journal #%d', $transactionJournal->id));
        // update both account balances, but limit to this transaction or earlier.
        foreach ($transactionJournal->transactions as $transaction) {
            self::recalculate($transaction->account, $transactionJournal);
        }
    }

    private static function recalculateJournals(?Account $account): void
    {
        $query = Transaction::groupBy(['transactions.account_id', 'transaction_journals.id', 'transactions.transaction_currency_id', 'transactions.foreign_currency_id']);
        $query->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id');
        $query->orderBy('transaction_journals.date', 'asc');
        if (null !== $account) {
            $query->where('transactions.account_id', $account->id);
        }
        $result  = $query->get(['transactions.account_id', 'transaction_journals.id', 'transactions.transaction_currency_id', 'transactions.foreign_currency_id', \DB::raw('SUM(transactions.amount) as sum_amount'), \DB::raw('SUM(transactions.foreign_amount) as sum_foreign_amount')]);
        $amounts = [];

        /** @var stdClass $row */
        foreach ($result as $row) {
            $account             = (int) $row->account_id;
            $transactionCurrency = (int) $row->transaction_currency_id;
            $foreignCurrency     = (int) $row->foreign_currency_id;
            $sumAmount           = $row->sum_amount;
            $sumForeignAmount    = $row->sum_foreign_amount;
            $journalId           = (int) $row->id;

            // new amounts:
            $amounts[$account][$transactionCurrency] = bcadd($amounts[$account][$transactionCurrency] ?? '0', $sumAmount ?? '0');
            $amounts[$account][$foreignCurrency]     = bcadd($amounts[$account][$foreignCurrency] ?? '0', $sumForeignAmount ?? '0');

            // first create for normal currency:
            $entry          = self::getAccountBalanceByJournal('balance_after_journal', $account, $journalId, $transactionCurrency);
            $entry->balance = $amounts[$account][$transactionCurrency];
            $entry->save();

            // then do foreign amount, if present:
            if ($foreignCurrency > 0) {
                $entry          = self::getAccountBalanceByJournal('balance_after_journal', $account, $journalId, $foreignCurrency);
                $entry->balance = $amounts[$account][$foreignCurrency];
                $entry->save();
            }
        }

        // select transactions.account_id, transaction_journals.id, transactions.transaction_currency_id, transactions.foreign_currency_id, sum(transactions.amount), sum(transactions.foreign_amount)
        //
        //from transactions
        //
        //left join transaction_journals ON transaction_journals.id = transactions.transaction_journal_id
        //
        //group by account_id, transaction_journals.id, transaction_currency_id, foreign_currency_id
        //order by transaction_journals.date desc


    }
}
