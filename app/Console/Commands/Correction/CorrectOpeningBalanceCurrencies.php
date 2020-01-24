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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Console\Command;
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
        // get all OB journals:
        $set = TransactionJournal
            ::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->whereNull('transaction_journals.deleted_at')
            ->where('transaction_types.type', TransactionType::OPENING_BALANCE)->get(['transaction_journals.*']);

        $this->line(sprintf('Going to verify %d opening balance transactions.', $set->count()));
        $count = 0;
        /** @var TransactionJournal $journal */
        foreach ($set as $journal) {
            $count += $this->correctJournal($journal);
        }

        if ($count > 0) {
            $this->line(sprintf('Corrected %d opening balance transactions.', $count));
        }
        if (0 === $count) {
            $this->info('There was nothing to fix in the opening balance transactions.');
        }

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
            $this->warn(sprintf('Transaction journal #%d has no valid account. Cant fix this line.', $journal->id));

            return 0;
        }
        Log::debug(sprintf('Found %s #%d "%s".', $account->accountType->type, $account->id, $account->name));
        $currency = $this->getCurrency($account);
        Log::debug(sprintf('Found currency #%d (%s)', $currency->id, $currency->code));

        // update journal and all transactions:
        $this->setCurrency($journal, $currency);

        return 1;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Account|null
     */
    private function getAccount(TransactionJournal $journal): ?Account
    {
        $excluded     = [];
        $transactions = $journal->transactions()->with(['account', 'account.accountType'])->get();
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $account = $transaction->account;
            if (null !== $account) {
                if (AccountType::INITIAL_BALANCE !== $account->accountType->type) {
                    return $account;
                }
            }
        }

        return null;
    }

    /**
     * @param Account $account
     *
     * @return TransactionCurrency
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
     */
    private function setCurrency(TransactionJournal $journal, TransactionCurrency $currency): void
    {
        $journal->transaction_currency_id = $currency->id;
        $journal->save();

        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            $transaction->transaction_currency_id = $currency->id;
            $transaction->save();
        }
    }
}
