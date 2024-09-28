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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountBalance;
use FireflyIII\Models\Transaction;
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
     * Recalculate all balances.
     */
    public static function forceRecalculateAll(): void
    {
        Transaction::whereNull('deleted_at')->update(['balance_dirty' => true]);
        $object = new self();
        $object->optimizedCalculation(new Collection());
    }

    /**
     * Recalculate all balances.
     */
    public static function recalculateAll(): void
    {
        $object = new self();
        $object->optimizedCalculation(new Collection());
    }

    public static function recalculateForJournal(TransactionJournal $transactionJournal): void
    {
        Log::debug(__METHOD__);
        $object = new self();

        // recalculate the involved accounts:
        $accounts = new Collection();
        foreach ($transactionJournal->transactions as $transaction) {
            $accounts->push($transaction->account);
        }
        $object->optimizedCalculation($accounts, $transactionJournal->date);
    }

    private function getLatestBalance(int $accountId, int $currencyId, ?Carbon $notBefore): string
    {
        if (null === $notBefore) {
            Log::debug('getLatestBalance: no notBefore date, returning 0');
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
        $balance = $first->balance_after ?? '0';
        Log::debug(sprintf('getLatestBalance: found balance: %s in transaction #%d', $balance, $first->id ?? 0));
        return $balance;
    }

    private function getAccountBalanceByAccount(int $account, int $currency): AccountBalance
    {
        $query = AccountBalance::where('title', 'balance')->where('account_id', $account)->where('transaction_currency_id', $currency);

        $entry = $query->first();
        if (null !== $entry) {
            // Log::debug(sprintf('Found account balance "balance" for account #%d and currency #%d: %s', $account, $currency, $entry->balance));

            return $entry;
        }
        $entry                          = new AccountBalance();
        $entry->title                   = 'balance';
        $entry->account_id              = $account;
        $entry->transaction_currency_id = $currency;
        $entry->balance                 = '0';
        $entry->save();
        // Log::debug(sprintf('Created new account balance for account #%d and currency #%d: %s', $account, $currency, $entry->balance));

        return $entry;
    }

    private function optimizedCalculation(Collection $accounts, ?Carbon $notBefore = null): void
    {
        Log::debug('start of optimizedCalculation');
        if (false === config('firefly.feature_flags.running_balance_column')) {
            Log::debug('optimizedCalculation is disabled, return.');
            return;
        }

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

        /** @var Transaction $entry */
        foreach ($set as $entry) {
            // start with empty array:
            $balances[$entry->account_id]                                  ??= [];
            $balances[$entry->account_id][$entry->transaction_currency_id] ??= $this->getLatestBalance($entry->account_id, $entry->transaction_currency_id, $notBefore);

            // before and after are easy:
            $before = $balances[$entry->account_id][$entry->transaction_currency_id];
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
            $balances[$entry->account_id][$entry->transaction_currency_id] = $after;
        }
        Log::debug(sprintf('end of optimizedCalculation, corrected %d balance(s)', $count));
        // then update all transactions.

        // ?? something with accounts?
    }

    private function getAccountBalanceByJournal(string $title, int $account, int $journal, int $currency): AccountBalance
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

    private function recalculateLatest(?Account $account): void
    {
        $query = Transaction::groupBy(['transactions.account_id', 'transactions.transaction_currency_id', 'transactions.foreign_currency_id']);

        if (null !== $account) {
            $query->where('transactions.account_id', $account->id);
        }
        $result = $query->get(['transactions.account_id', 'transactions.transaction_currency_id', 'transactions.foreign_currency_id', \DB::raw('SUM(transactions.amount) as sum_amount'), \DB::raw('SUM(transactions.foreign_amount) as sum_foreign_amount')]);

        // reset account balances:
        $this->resetAccountBalancesByAccount('balance', $account);

        /** @var \stdClass $row */
        foreach ($result as $row) {
            $account             = (int) $row->account_id;
            $transactionCurrency = (int) $row->transaction_currency_id;
            $foreignCurrency     = (int) $row->foreign_currency_id;
            $sumAmount           = (string) $row->sum_amount;
            $sumForeignAmount    = (string) $row->sum_foreign_amount;
            $sumAmount           = '' === $sumAmount ? '0' : $sumAmount;
            $sumForeignAmount    = '' === $sumForeignAmount ? '0' : $sumForeignAmount;

            // at this point SQLite may return scientific notation because why not. Terrible.
            $sumAmount        = app('steam')->floatalize($sumAmount);
            $sumForeignAmount = app('steam')->floatalize($sumForeignAmount);

            // first create for normal currency:
            $entry = $this->getAccountBalanceByAccount($account, $transactionCurrency);

            try {
                $entry->balance = bcadd((string) $entry->balance, $sumAmount);
            } catch (\ValueError $e) {
                $message = sprintf('[a] Could not add "%s" to "%s": %s', $entry->balance, $sumAmount, $e->getMessage());
                Log::error($message);

                throw new FireflyException($message, 0, $e);
            }
            $entry->save();

            // then do foreign amount, if present:
            if ($foreignCurrency > 0) {
                $entry = $this->getAccountBalanceByAccount($account, $foreignCurrency);

                try {
                    $entry->balance = bcadd((string) $entry->balance, $sumForeignAmount);
                } catch (\ValueError $e) {
                    $message = sprintf('[b] Could not add "%s" to "%s": %s', $entry->balance, $sumForeignAmount, $e->getMessage());
                    Log::error($message);

                    throw new FireflyException($message, 0, $e);
                }
                $entry->save();
            }
        }
        Log::debug(sprintf('Recalculated %d account balance(s)', $result->count()));
    }

    private function resetAccountBalancesByAccount(string $title, ?Account $account): void
    {
        if (null === $account) {
            $count = AccountBalance::whereNotNull('updated_at')->where('title', $title)->update(['balance' => '0']);
            Log::debug(sprintf('Set %d account balance(s) to zero.', $count));

            return;
        }
        $count = AccountBalance::where('account_id', $account->id)->where('title', $title)->update(['balance' => '0']);
        Log::debug(sprintf('Set %d account balance(s) of account #%d to zero.', $count, $account->id));
    }

    /**
     * Als je alles opnieuw doet, verzamel je alle transactions en het bedrag en zet je dat neer als "balance after
     * journal". Dat betekent, netjes op volgorde van datum en doorrekenen.
     *
     * Zodra je een transaction journal verplaatst (datum) moet je dat journal en alle latere journals opnieuw doen.
     * Maar dan moet je van de account wel een beginnetje hebben, namelijk de balance tot en met dat moment.
     *
     *  1. Dus dan search je eerst naar die SUM, som alle transactions eerder dan (niet inclusief) de journal.
     *  2. En vanaf daar pak je alle journals op of na de journal (dus ook de journal zelf) en begin je door te tellen.
     *  3. Elke voorbij gaande journal entry "balance_after_journal" geef je een update of voeg je toe.
     */
    private function recalculateJournals(?Account $account, ?TransactionJournal $transactionJournal): void
    {
        $query = Transaction::groupBy(['transactions.account_id', 'transaction_journals.id', 'transactions.transaction_currency_id', 'transactions.foreign_currency_id']);
        $query->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id');
        $query->orderBy('transaction_journals.date', 'asc');
        $amounts = [];
        if (null !== $account) {
            $query->where('transactions.account_id', $account->id);
        }
        if (null !== $account && null !== $transactionJournal) {
            $query->where('transaction_journals.date', '>=', $transactionJournal->date);
            $amounts = $this->getStartAmounts($account, $transactionJournal);
        }
        $result = $query->get(['transactions.account_id', 'transaction_journals.id', 'transactions.transaction_currency_id', 'transactions.foreign_currency_id', \DB::raw('SUM(transactions.amount) as sum_amount'), \DB::raw('SUM(transactions.foreign_amount) as sum_foreign_amount')]);

        /** @var \stdClass $row */
        foreach ($result as $row) {
            $account             = (int) $row->account_id;
            $transactionCurrency = (int) $row->transaction_currency_id;
            $foreignCurrency     = (int) $row->foreign_currency_id;
            $sumAmount           = (string) $row->sum_amount;
            $sumForeignAmount    = (string) $row->sum_foreign_amount;
            $journalId           = (int) $row->id;

            // check for empty strings
            $sumAmount        = '' === $sumAmount ? '0' : $sumAmount;
            $sumForeignAmount = '' === $sumForeignAmount ? '0' : $sumForeignAmount;

            // new amounts:
            $amounts[$account][$transactionCurrency] = bcadd($amounts[$account][$transactionCurrency] ?? '0', $sumAmount);
            $amounts[$account][$foreignCurrency]     = bcadd($amounts[$account][$foreignCurrency] ?? '0', $sumForeignAmount);

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
        // from transactions
        //
        // left join transaction_journals ON transaction_journals.id = transactions.transaction_journal_id
        //
        // group by account_id, transaction_journals.id, transaction_currency_id, foreign_currency_id
        // order by transaction_journals.date desc
    }

    private function getStartAmounts(Account $account, TransactionJournal $journal): array
    {
        exit('here we are 1');

        return [];
    }
}
