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

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class CorrectsOpeningBalanceCurrencies extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Will make sure that opening balance transaction currencies match the account they\'re for.';
    protected $signature   = 'correction:opening-balance-currencies';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $journals = $this->getJournals();
        $count    = 0;

        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $count += $this->correctJournal($journal);
        }

        if ($count > 0) {
            $message = sprintf('Corrected %d opening balance transaction(s).', $count);
            $this->friendlyInfo($message);
        }

        return 0;
    }

    private function getJournals(): Collection
    {
        /** @var Collection */
        return TransactionJournal::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->whereNull('transaction_journals.deleted_at')
            ->where('transaction_types.type', TransactionTypeEnum::OPENING_BALANCE->value)->get(['transaction_journals.*'])
        ;
    }

    private function correctJournal(TransactionJournal $journal): int
    {
        // get the asset account for this opening balance:
        $account = $this->getAccount($journal);
        if (!$account instanceof Account) {
            $message = sprintf('Transaction journal #%d has no valid account. Can\'t fix this line.', $journal->id);
            app('log')->warning($message);
            $this->friendlyError($message);

            return 0;
        }

        // update journal and all transactions:
        return $this->setCorrectCurrency($account, $journal);
    }

    private function getAccount(TransactionJournal $journal): ?Account
    {
        $transactions = $journal->transactions()->get();

        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            /** @var null|Account $account */
            $account = $transaction->account()->first();
            if (null !== $account && AccountTypeEnum::INITIAL_BALANCE->value !== $account->accountType()->first()->type) {
                return $account;
            }
        }

        return null;
    }

    private function setCorrectCurrency(Account $account, TransactionJournal $journal): int
    {
        $currency = $this->getCurrency($account);
        $count    = 0;
        if ((int) $journal->transaction_currency_id !== $currency->id) {
            $journal->transaction_currency_id = $currency->id;
            $journal->save();
            $count                            = 1;
        }

        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            if ($transaction->transaction_currency_id !== $currency->id) {
                $transaction->transaction_currency_id = $currency->id;
                $transaction->save();
                $count                                = 1;
            }
        }

        return $count;
    }

    private function getCurrency(Account $account): TransactionCurrency
    {
        /** @var AccountRepositoryInterface $repos */
        $repos = app(AccountRepositoryInterface::class);
        $repos->setUser($account->user);

        return $repos->getAccountCurrency($account) ?? app('amount')->getNativeCurrencyByUserGroup($account->userGroup);
    }
}
