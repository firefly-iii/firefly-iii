<?php

/*
 * FixTransactionTypes.php
 * Copyright (c) 2021 james@firefly-iii.org
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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Class FixTransactionTypes
 */
class FixTransactionTypes extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make sure all transactions are of the correct type, based on source + dest.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:fix-transaction-types';


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $start    = microtime(true);
        $count    = 0;
        $journals = $this->collectJournals();
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $fixed = $this->fixJournal($journal);
            if (true === $fixed) {
                $count++;
            }
        }
        $end = round(microtime(true) - $start, 2);
        if ($count > 0) {
            $this->info(sprintf('Corrected transaction type of %d transaction journals in %s seconds.', $count, $end));

            return 0;
        }
        $this->line(sprintf('All transaction journals are of the correct transaction type (in %s seconds).', $end));

        return 0;
    }

    /**
     * Collect all transaction journals.
     *
     * @return Collection
     */
    private function collectJournals(): Collection
    {
        return TransactionJournal
            ::with(['transactionType', 'transactions', 'transactions.account', 'transactions.account.accountType'])
            ->get();
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    private function fixJournal(TransactionJournal $journal): bool
    {
        $type = $journal->transactionType->type;
        try {
            $source      = $this->getSourceAccount($journal);
            $destination = $this->getDestinationAccount($journal);
        } catch (FireflyException $e) {
            $this->error($e->getMessage());

            return false;
        }
        $expectedType = (string)config(sprintf('firefly.account_to_transaction.%s.%s', $source->accountType->type, $destination->accountType->type));
        if ($expectedType !== $type) {
            $this->line(sprintf('Transaction journal #%d was of type "%s" but is corrected to "%s"', $journal->id, $type, $expectedType));
            $this->changeJournal($journal, $expectedType);

            return true;
        }

        return false;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Account
     * @throws FireflyException
     */
    private function getSourceAccount(TransactionJournal $journal): Account
    {
        $collection = $journal->transactions->filter(
            static function (Transaction $transaction) {
                return $transaction->amount < 0;
            }
        );
        if (0 === $collection->count()) {
            throw new FireflyException(sprintf('Journal #%d has no source transaction.', $journal->id));
        }
        if (1 !== $collection->count()) {
            throw new FireflyException(sprintf('Journal #%d has multiple source transactions.', $journal->id));
        }
        /** @var Transaction $transaction */
        $transaction = $collection->first();
        $account     = $transaction->account;
        if (null === $account) {
            throw new FireflyException(sprintf('Journal #%d, transaction #%d has no source account.', $journal->id, $transaction->id));
        }

        return $account;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Account
     * @throws FireflyException
     */
    private function getDestinationAccount(TransactionJournal $journal): Account
    {
        $collection = $journal->transactions->filter(
            static function (Transaction $transaction) {
                return $transaction->amount > 0;
            }
        );
        if (0 === $collection->count()) {
            throw new FireflyException(sprintf('Journal #%d has no destination transaction.', $journal->id));
        }
        if (1 !== $collection->count()) {
            throw new FireflyException(sprintf('Journal #%d has multiple destination transactions.', $journal->id));
        }
        /** @var Transaction $transaction */
        $transaction = $collection->first();
        $account     = $transaction->account;
        if (null === $account) {
            throw new FireflyException(sprintf('Journal #%d, transaction #%d has no destination account.', $journal->id, $transaction->id));
        }

        return $account;
    }

    /**
     * @param TransactionJournal $journal
     * @param string             $expectedType
     */
    private function changeJournal(TransactionJournal $journal, string $expectedType): void
    {
        $type = TransactionType::whereType($expectedType)->first();
        if (null !== $type) {
            $journal->transaction_type_id = $type->id;
            $journal->save();
        }
    }
}
