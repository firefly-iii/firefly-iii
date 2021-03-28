<?php

/**
 * CorrectOpeningBalanceCurrencies.php
 * Copyright (c) 2020 james@firefly-iii.org
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Console\Command;
use JsonException;
use Log;

/**
 * Class CorrectOpeningBalanceCurrencies
 */
class CorrectOpeningBalanceCurrencies extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will make sure that opening balance transaction currencies match the account they\'re for.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:fix-ob-currencies';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        // get all OB journals:
        $set = TransactionJournal
            ::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->whereNull('transaction_journals.deleted_at')
            ->where('transaction_types.type', TransactionType::OPENING_BALANCE)->get(['transaction_journals.*']);

        $this->line(sprintf('Going to verify %d opening balance transactions.', $set->count()));
        $count = 0;
        /** @var TransactionJournal $journal */
        foreach ($set as $journal) {
            Log::debug(sprintf('Going to fix journal #%d', $journal->id));
            $count += $this->correctJournal($journal);
            Log::debug(sprintf('Done, count is now %d', $count));
        }

        if ($count > 0) {
            $message = sprintf('Corrected %d opening balance transaction(s).', $count);
            Log::debug($message);
            $this->line($message);
        }
        if (0 === $count) {
            $message = 'There was nothing to fix in the opening balance transactions.';
            Log::debug($message);
            $this->info($message);
        }

        Log::debug(sprintf('Done with %s', __METHOD__));

        return 0;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return int
     */
    private function correctJournal(TransactionJournal $journal): int
    {
        Log::debug(sprintf('Going to correct journal #%d', $journal->id));
        // get the asset account for this opening balance:
        $account = $this->getAccount($journal);
        if (null === $account) {
            $message = sprintf('Transaction journal #%d has no valid account. Cant fix this line.', $journal->id);
            Log::warning($message);
            $this->warn($message);

            return 0;
        }
        Log::debug(sprintf('Found "%s" #%d "%s".', $account->accountType->type, $account->id, $account->name));
        $currency = $this->getCurrency($account);
        Log::debug(sprintf('Found currency #%d (%s)', $currency->id, $currency->code));

        // update journal and all transactions:
        return $this->setCurrency($journal, $currency);
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Account|null
     */
    private function getAccount(TransactionJournal $journal): ?Account
    {
        $transactions = $journal->transactions()->get();
        Log::debug(sprintf('Found %d transactions for journal #%d.', $transactions->count(), $journal->id));
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            Log::debug(sprintf('Testing transaction #%d', $transaction->id));
            /** @var Account $account */
            $account = $transaction->account()->first();
            if (null !== $account && AccountType::INITIAL_BALANCE !== $account->accountType()->first()->type) {
                Log::debug(sprintf('Account of transaction #%d is opposite of IB account (%s).', $transaction->id, $account->accountType()->first()->type));

                return $account;
            }
        }
        Log::debug('Found no IB account in transactions of journal.');

        return null;
    }

    /**
     * @param Account $account
     *
     * @return TransactionCurrency
     * @throws JsonException
     */
    private function getCurrency(Account $account): TransactionCurrency
    {
        /** @var AccountRepositoryInterface $repos */
        $repos = app(AccountRepositoryInterface::class);
        $repos->setUser($account->user);

        return $repos->getAccountCurrency($account) ?? app('amount')->getDefaultCurrencyByUser($account->user);
    }

    /**
     * @param TransactionJournal  $journal
     * @param TransactionCurrency $currency
     *
     * @return int
     */
    private function setCurrency(TransactionJournal $journal, TransactionCurrency $currency): int
    {
        Log::debug('Now in setCurrency');
        $count = 0;
        if ((int)$journal->transaction_currency_id !== (int)$currency->id) {
            Log::debug(sprintf('Currency ID of journal #%d was #%d, now set to #%d', $journal->id, $journal->transaction_currency_id, $currency->id));
            $journal->transaction_currency_id = $currency->id;
            $journal->save();
            $count = 1;
        }

        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            if ((int)$transaction->transaction_currency_id !== (int)$currency->id) {
                Log::debug(
                    sprintf('Currency ID of transaction #%d was #%d, now set to #%d', $transaction->id, $transaction->transaction_currency_id, $currency->id)
                );
                $transaction->transaction_currency_id = $currency->id;
                $transaction->save();
                $count = 1;
            }
        }
        Log::debug(sprintf('Return %d', $count));

        return $count;
    }
}
