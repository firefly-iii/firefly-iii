<?php

/**
 * FixUnevenAmount.php
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
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Models\AccountBalanceCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ValueError;
use stdClass;

class CorrectsUnevenAmount extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Fix journals with uneven amounts.';
    protected $signature   = 'correction:uneven-amounts';
    private int $count;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->count = 0;
        // convert transfers with foreign currency info where the amount is NOT uneven (it should be)
        $this->convertOldStyleTransfers();

        // convert old-style transactions between assets and liabilities.
        $this->convertOldStyleTransactions();

        $this->fixUnevenAmounts();
        $this->matchCurrencies();
        if (true === config('firefly.feature_flags.running_balance_column')) {
            $this->friendlyInfo('Will recalculate transaction running balance columns. This may take a LONG time. Please be patient.');
            AccountBalanceCalculator::recalculateAll(false);
            $this->friendlyInfo('Done recalculating transaction running balance columns.');
        }

        return 0;
    }

    private function convertOldStyleTransfers(): void
    {
        Log::debug('convertOldStyleTransfers()');
        // select transactions with a foreign amount and a foreign currency. and it's a transfer. and they are different.
        $transactions = Transaction::distinct()
            ->leftJoin('transaction_journals', 'transaction_journals.id', 'transactions.transaction_journal_id')
            ->leftJoin('transaction_types', 'transaction_types.id', 'transaction_journals.transaction_type_id')
            ->where('transaction_types.type', TransactionTypeEnum::TRANSFER->value)
            ->whereNotNull('foreign_currency_id')
            ->whereNotNull('foreign_amount')->get(['transactions.transaction_journal_id'])
        ;
        $count        = 0;

        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            /** @var null|TransactionJournal $journal */
            $journal     = TransactionJournal::find($transaction->transaction_journal_id);
            if (null === $journal) {
                Log::debug('Found no journal, continue.');

                continue;
            }
            // needs to be a transfer.
            if (TransactionTypeEnum::TRANSFER->value !== $journal->transactionType->type) {
                Log::debug('Must be a transfer, continue.');

                continue;
            }

            /** @var null|Transaction $destination */
            $destination = $journal->transactions()->where('amount', '>', 0)->first();

            /** @var null|Transaction $source */
            $source      = $journal->transactions()->where('amount', '<', 0)->first();
            if (null === $destination || null === $source) {
                Log::debug('Source or destination transaction is NULL, continue.');

                // will be picked up later.
                continue;
            }
            if ($source->transaction_currency_id === $destination->transaction_currency_id) {
                Log::debug('Ready to swap data between transactions.');
                $destination->foreign_currency_id     = $source->transaction_currency_id;
                $destination->foreign_amount          = app('steam')->positive($source->amount);
                $destination->transaction_currency_id = $source->foreign_currency_id;
                $destination->amount                  = app('steam')->positive($source->foreign_amount);
                $destination->balance_dirty           = true;
                $source->balance_dirty                = true;
                $destination->save();
                $source->save();
                $this->friendlyWarning(sprintf('Corrected foreign amounts of transfer #%d.', $journal->id));
                ++$count;
            }
        }
        if (0 === $count) {
            return;
        }
        $this->friendlyPositive(sprintf('Fixed %d transfer(s) with unbalanced amounts.', $count));
    }

    private function fixUnevenAmounts(): void
    {
        Log::debug('fixUnevenAmounts()');
        $journals = DB::table('transactions')
            ->groupBy('transaction_journal_id')
            ->whereNull('deleted_at')
            ->get(['transaction_journal_id', DB::raw('SUM(amount) AS the_sum')])
        ;

        /** @var stdClass $entry */
        foreach ($journals as $entry) {
            $sum = (string) $entry->the_sum;
            $sum = Steam::floatalize($sum);
            if (!is_numeric($sum)
                || '' === $sum // @phpstan-ignore-line
                || str_contains($sum, 'e')
                || str_contains($sum, ',')) {
                $message = sprintf(
                    'Journal #%d has an invalid sum ("%s"). No sure what to do.',
                    $entry->transaction_journal_id,
                    $entry->the_sum
                );
                $this->friendlyWarning($message);
                app('log')->warning($message);
                ++$this->count;

                continue;
            }
            $res = -1;

            try {
                $res = bccomp($sum, '0');
            } catch (ValueError $e) {
                $this->friendlyError(sprintf('Could not bccomp("%s", "0").', $sum));
                Log::error($e->getMessage());
                Log::error($e->getTraceAsString());
            }
            if (0 !== $res) {
                $this->fixJournal((int) $entry->transaction_journal_id);
            }
        }
    }

    private function fixJournal(int $param): void
    {
        // one of the transactions is bad.
        $journal             = TransactionJournal::find($param);
        if (null === $journal) {
            return;
        }

        /** @var null|Transaction $source */
        $source              = $journal->transactions()->where('amount', '<', 0)->first();

        if (null === $source) {
            $this->friendlyError(
                sprintf(
                    'Journal #%d ("%s") has no source transaction. It will be deleted to maintain database consistency.',
                    $journal->id ?? 0,
                    $journal->description ?? ''
                )
            );
            Transaction::where('transaction_journal_id', $journal->id ?? 0)->forceDelete();
            TransactionJournal::where('id', $journal->id ?? 0)->forceDelete();
            ++$this->count;

            return;
        }

        $amount              = bcmul('-1', (string) $source->amount);

        // fix amount of destination:
        /** @var null|Transaction $destination */
        $destination         = $journal->transactions()->where('amount', '>', 0)->first();

        if (null === $destination) {
            $this->friendlyError(
                sprintf(
                    'Journal #%d ("%s") has no destination transaction. It will be deleted to maintain database consistency.',
                    $journal->id ?? 0,
                    $journal->description ?? ''
                )
            );

            Transaction::where('transaction_journal_id', $journal->id ?? 0)->forceDelete();
            TransactionJournal::where('id', $journal->id ?? 0)->forceDelete();
            ++$this->count;

            return;
        }

        // may still be able to salvage this journal if it is a transfer with foreign currency info
        if ($this->isForeignCurrencyTransfer($journal) || $this->isBetweenAssetAndLiability($journal)) {
            Log::debug(sprintf('Can skip foreign currency transfer / asset+liability transaction #%d.', $journal->id));

            return;
        }

        $message             = sprintf('Sum of journal #%d is not zero, journal is broken and now fixed.', $journal->id);

        $this->friendlyWarning($message);
        app('log')->warning($message);

        $destination->amount = $amount;
        $destination->save();

        $message             = sprintf('Corrected amount in transaction journal #%d', $param);
        $this->friendlyInfo($message);
        ++$this->count;
    }

    private function isForeignCurrencyTransfer(TransactionJournal $journal): bool
    {
        if (TransactionTypeEnum::TRANSFER->value !== $journal->transactionType->type) {
            return false;
        }

        /** @var Transaction $destination */
        $destination = $journal->transactions()->where('amount', '>', 0)->first();

        /** @var Transaction $source */
        $source      = $journal->transactions()->where('amount', '<', 0)->first();

        // safety catch on NULL should not be necessary, we just had that catch.
        // source amount = dest foreign amount
        // source currency = dest foreign currency
        // dest amount = source foreign currency
        // dest currency = source foreign currency

        //        Log::debug(sprintf('[a] %s', bccomp(app('steam')->positive($source->amount), app('steam')->positive($destination->foreign_amount))));
        //        Log::debug(sprintf('[b] %s', bccomp(app('steam')->positive($destination->amount), app('steam')->positive($source->foreign_amount))));
        //        Log::debug(sprintf('[c] %s', var_export($source->transaction_currency_id === $destination->foreign_currency_id,true)));
        //        Log::debug(sprintf('[d] %s', var_export((int) $destination->transaction_currency_id ===(int)  $source->foreign_currency_id, true)));

        if (0 === bccomp((string) app('steam')->positive($source->amount), (string) app('steam')->positive($destination->foreign_amount))
            && $source->transaction_currency_id === $destination->foreign_currency_id
            && 0 === bccomp((string) app('steam')->positive($destination->amount), (string) app('steam')->positive($source->foreign_amount))
            && (int) $destination->transaction_currency_id === (int) $source->foreign_currency_id
        ) {
            return true;
        }

        return false;
    }

    private function matchCurrencies(): void
    {
        $journals = TransactionJournal::leftJoin('transactions', 'transaction_journals.id', 'transactions.transaction_journal_id')
            ->where('transactions.transaction_currency_id', '!=', DB::raw('transaction_journals.transaction_currency_id'))
            ->get(['transaction_journals.*'])
        ;

        $count    = 0;

        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            if (!$this->isForeignCurrencyTransfer($journal) && !$this->isBetweenAssetAndLiability($journal)) {
                Transaction::where('transaction_journal_id', $journal->id)->update(['transaction_currency_id' => $journal->transaction_currency_id]);
                ++$count;

                continue;
            }
            Log::debug(sprintf('Can skip foreign currency transfer or transaction between asset and liability #%d.', $journal->id));
        }
        if (0 === $count) {
            return;
        }

        $this->friendlyPositive(sprintf('Fixed %d journal(s) with mismatched currencies.', $journals->count()));
    }

    private function isBetweenAssetAndLiability(TransactionJournal $journal): bool
    {
        /** @var Transaction $sourceTransaction */
        $sourceTransaction      = $journal->transactions()->where('amount', '<', 0)->first();

        /** @var Transaction $destinationTransaction */
        $destinationTransaction = $journal->transactions()->where('amount', '>', 0)->first();
        if (null === $sourceTransaction || null === $destinationTransaction) {
            Log::warning('Either transaction is false, stop.');

            return false;
        }
        if (null === $sourceTransaction->foreign_amount || null === $destinationTransaction->foreign_amount) {
            Log::warning('Either foreign amount is false, stop.');

            return false;
        }

        $source                 = $sourceTransaction->account;
        $destination            = $destinationTransaction->account;

        if (null === $source || null === $destination) {
            Log::warning('Either is false, stop.');

            return false;
        }
        $sourceTypes            = [AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value];

        // source is liability, destination is asset
        if (in_array($source->accountType->type, $sourceTypes, true) && AccountTypeEnum::ASSET->value === $destination->accountType->type) {
            Log::debug('Source is a liability account, destination is an asset account, return TRUE.');

            return true;
        }
        // source is asset, destination is liability
        if (in_array($destination->accountType->type, $sourceTypes, true) && AccountTypeEnum::ASSET->value === $source->accountType->type) {
            Log::debug('Destination is a liability account, source is an asset account, return TRUE.');

            return true;
        }

        return false;
    }

    private function convertOldStyleTransactions(): void
    {

        /** @var AccountRepositoryInterface $repository */
        $repository   = app(AccountRepositoryInterface::class);
        Log::debug('convertOldStyleTransactions()');
        $count        = 0;
        $transactions = Transaction::distinct()
            ->leftJoin('transaction_journals', 'transaction_journals.id', 'transactions.transaction_journal_id')
            ->leftJoin('transaction_types', 'transaction_types.id', 'transaction_journals.transaction_type_id')
            ->leftJoin('accounts', 'accounts.id', 'transactions.account_id')
            ->leftJoin('account_types', 'account_types.id', 'accounts.account_type_id')
            ->whereNot('transaction_types.type', TransactionTypeEnum::TRANSFER->value)
            ->whereNotNull('foreign_currency_id')
            ->whereNotNull('foreign_amount')
            ->whereIn('account_types.type', [AccountTypeEnum::ASSET->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::LOAN->value])
            ->get(['transactions.transaction_journal_id'])
        ;

        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            /** @var null|TransactionJournal $journal */
            $journal        = TransactionJournal::find($transaction->transaction_journal_id);
            $repository->setUser($journal->user);
            if (null === $journal) {
                Log::debug('Found no journal, continue.');

                continue;
            }
            if (!$this->isBetweenAssetAndLiability($journal)) {
                Log::debug('Not between asset and liability, continue.');

                continue;
            }
            $source         = $journal->transactions()->where('amount', '<', 0)->first();
            $destination    = $journal->transactions()->where('amount', '>', 0)->first();
            $sourceAccount  = $source->account;
            $destAccount    = $destination->account;
            $sourceCurrency = $repository->getAccountCurrency($sourceAccount);
            $destCurrency   = $repository->getAccountCurrency($destAccount);
            if (null === $source || null === $destination) {
                Log::debug('Either transaction is NULL, continue.');

                continue;
            }
            if (0 === bccomp((string) $source->amount, (string) $source->foreign_amount) && 0 === bccomp((string) $source->foreign_amount, (string) $source->amount)) {
                Log::debug('Already fixed, continue.');

                continue;
            }
            // source transaction. Switch info when does not match.
            if ((int) $source->transaction_currency_id !== (int) $sourceCurrency->id) {
                Log::debug(sprintf('Ready to swap data in transaction #%d.', $source->id));
                // swap amounts.
                $amount                          = $source->amount;
                $currency                        = $source->transaction_currency_id;
                $source->amount                  = $source->foreign_amount;
                $source->transaction_currency_id = $source->foreign_currency_id;
                $source->foreign_amount          = $amount;
                $source->foreign_currency_id     = $currency;
                $source->saveQuietly();
                $source->refresh();
                //                Log::debug(sprintf('source->amount                  = %s', $source->amount));
                //                Log::debug(sprintf('source->transaction_currency_id = %s', $source->transaction_currency_id));
                //                Log::debug(sprintf('source->foreign_amount          = %s', $source->foreign_amount));
                //                Log::debug(sprintf('source->foreign_currency_id     = %s', $source->foreign_currency_id));
                ++$count;
            }
            // same but for destination
            if ((int) $destination->transaction_currency_id !== (int) $destCurrency->id) {
                ++$count;
                Log::debug(sprintf('Ready to swap data in transaction #%d.', $destination->id));
                // swap amounts.
                $amount                               = $destination->amount;
                $currency                             = $destination->transaction_currency_id;
                $destination->amount                  = $destination->foreign_amount;
                $destination->transaction_currency_id = $destination->foreign_currency_id;
                $destination->foreign_amount          = $amount;
                $destination->foreign_currency_id     = $currency;
                $destination->balance_dirty           = true;
                $destination->saveQuietly();
                $destination->refresh();
                //                Log::debug(sprintf('destination->amount                  = %s', $destination->amount));
                //                Log::debug(sprintf('destination->transaction_currency_id = %s', $destination->transaction_currency_id));
                //                Log::debug(sprintf('destination->foreign_amount          = %s', $destination->foreign_amount));
                //                Log::debug(sprintf('destination->foreign_currency_id     = %s', $destination->foreign_currency_id));
            }


            //            // only fix the destination transaction
            //            $destination->foreign_currency_id     = $source->transaction_currency_id;
            //            $destination->foreign_amount          = app('steam')->positive($source->amount);
            //            $destination->transaction_currency_id = $source->foreign_currency_id;
            //            $destination->amount                  = app('steam')->positive($source->foreign_amount);
            //            $destination->balance_dirty           = true;
            //            $source->balance_dirty                = true;
            //            $destination->save();
            //            $source->save();
            //            $this->friendlyWarning(sprintf('Corrected foreign amounts of transaction #%d.', $journal->id));
        }
        if (0 === $count) {
            return;
        }

        $this->friendlyPositive(sprintf('Fixed %d journal(s) with unbalanced amounts.', $count));
    }
}
