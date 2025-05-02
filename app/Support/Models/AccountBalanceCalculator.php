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

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountBalance;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class AccountBalanceCalculator
 *
 * This class started as a piece of code to create and calculate "account balance" objects, but they
 * are at the moment unused. Instead, each transaction gets a before/after balance and an indicator if this
 * balance is up-to-date. This class now contains some methods to recalculate those amounts.
 */
class AccountBalanceCalculator
{
    private function __construct()
    {
        // no-op
    }

    /**
     * Recalculate all account and transaction balances.
     */
    public static function recalculateAll(bool $forced): void
    {
        if ($forced) {
            Transaction::whereNull('deleted_at')->update(['balance_dirty' => true]);
            // also delete account balances.
            AccountBalance::whereNotNull('created_at')->delete();
        }
        $object = new self();
        $object->optimizedCalculation(new Collection());
    }

    private function optimizedCalculation(Collection $accounts, ?Carbon $notBefore = null): void
    {
        Log::debug('start of optimizedCalculation');
        if ($accounts->count() > 0) {
            Log::debug(sprintf('Limited to %d account(s)', $accounts->count()));
        }
        // collect all transactions and the change they make.
        $balances = [];
        $count    = 0;
        $query    = Transaction::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                               ->whereNull('transactions.deleted_at')
                               ->whereNull('transaction_journals.deleted_at')
            // this order is the same as GroupCollector, but in the exact reverse.
                               ->orderBy('transaction_journals.date', 'asc')
                               ->orderBy('transaction_journals.order', 'desc')
                               ->orderBy('transaction_journals.id', 'asc')
                               ->orderBy('transaction_journals.description', 'asc')
                               ->orderBy('transactions.amount', 'asc');
        if ($accounts->count() > 0) {
            $query->whereIn('transactions.account_id', $accounts->pluck('id')->toArray());
        }
        if (null !== $notBefore) {
            $notBefore->startOfDay();
            $query->where('transaction_journals.date', '>=', $notBefore);
        }

        $set = $query->get(['transactions.id', 'transactions.balance_dirty', 'transactions.transaction_currency_id', 'transaction_journals.date', 'transactions.account_id', 'transactions.amount']);
        Log::debug(sprintf('Counted %d transaction(s)', $set->count()));

        // the balance value is an array.
        // first entry is the balance, second is the date.

        /** @var Transaction $entry */
        foreach ($set as $entry) {
            // start with empty array:
            $balances[$entry->account_id]                                  ??= [];
            $balances[$entry->account_id][$entry->transaction_currency_id] ??= [$this->getLatestBalance($entry->account_id, $entry->transaction_currency_id, $notBefore), null];

            // before and after are easy:
            $before = $balances[$entry->account_id][$entry->transaction_currency_id][0];
            $after  = bcadd($before, $entry->amount);
            if (true === $entry->balance_dirty || $accounts->count() > 0) {
                // update the transaction:
                $entry->balance_before = $before;
                $entry->balance_after  = $after;
                $entry->balance_dirty  = false;
                $entry->saveQuietly(); // do not observe this change, or we get stuck in a loop.
                ++$count;
            }

            // then update the array:
            $balances[$entry->account_id][$entry->transaction_currency_id] = [$after, $entry->date];
        }
        Log::debug(sprintf('end of optimizedCalculation, corrected %d balance(s)', $count));
        // then update all transactions.

        // save all collected balances in their respective account objects.
        $this->storeAccountBalances($balances);
    }

    private function getLatestBalance(int $accountId, int $currencyId, ?Carbon $notBefore): string
    {
        if (null === $notBefore) {
            return '0';
        }
        Log::debug(sprintf('getLatestBalance: notBefore date is "%s", calculating', $notBefore->format('Y-m-d')));
        $query = Transaction::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                            ->whereNull('transactions.deleted_at')
                            ->where('transaction_journals.transaction_currency_id', $currencyId)
                            ->whereNull('transaction_journals.deleted_at')
            // this order is the same as GroupCollector
                            ->orderBy('transaction_journals.date', 'DESC')
                            ->orderBy('transaction_journals.order', 'ASC')
                            ->orderBy('transaction_journals.id', 'DESC')
                            ->orderBy('transaction_journals.description', 'DESC')
                            ->orderBy('transactions.amount', 'DESC')
                            ->where('transactions.account_id', $accountId);
        $notBefore->startOfDay();
        $query->where('transaction_journals.date', '<', $notBefore);

        $first   = $query->first(['transactions.id', 'transactions.balance_dirty', 'transactions.transaction_currency_id', 'transaction_journals.date', 'transactions.account_id', 'transactions.amount', 'transactions.balance_after']);
        $balance = (string) ($first->balance_after ?? '0');
        Log::debug(sprintf('getLatestBalance: found balance: %s in transaction #%d', $balance, $first->id ?? 0));

        return $balance;
    }

    private function storeAccountBalances(array $balances): void
    {
        /**
         * @var int   $accountId
         * @var array $currencies
         */
        foreach ($balances as $accountId => $currencies) {
            /** @var null|Account $account */
            $account = Account::find($accountId);
            if (null === $account) {
                Log::error(sprintf('Could not find account #%d, will not save account balance.', $accountId));

                continue;
            }

            /**
             * @var int   $currencyId
             * @var array $balance
             */
            foreach ($currencies as $currencyId => $balance) {
                /** @var null|TransactionCurrency $currency */
                $currency = TransactionCurrency::find($currencyId);
                if (null === $currency) {
                    Log::error(sprintf('Could not find currency #%d, will not save account balance.', $currencyId));

                    continue;
                }

                /** @var AccountBalance $object */
                $object          = $account->accountBalances()->firstOrCreate(
                    [
                        'title'                   => 'running_balance',
                        'balance'                 => '0',
                        'transaction_currency_id' => $currencyId,
                        'date'                    => $balance[1],
                        'date_tz'                 => $balance[1]?->format('e'),
                    ]
                );
                $object->balance = $balance[0];
                $object->date    = $balance[1];
                $object->date_tz = $balance[1]?->format('e');
                $object->saveQuietly();
            }
        }
    }

    public static function recalculateForJournal(TransactionJournal $transactionJournal): void
    {
        Log::debug(__METHOD__);
        $object = new self();

        $set = [];
        foreach ($transactionJournal->transactions as $transaction) {
            $set[$transaction->account_id] = $transaction->account;
        }
        $accounts = new Collection($set);
        $object->optimizedCalculation($accounts, $transactionJournal->date);
    }
}
