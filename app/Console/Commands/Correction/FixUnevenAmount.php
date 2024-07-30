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
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Support\Facades\Steam;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Class FixUnevenAmount
 */
class FixUnevenAmount extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Fix journals with uneven amounts.';
    protected $signature   = 'firefly-iii:fix-uneven-amount';
    private int $count;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->count = 0;
        $this->fixUnevenAmounts();
        $this->matchCurrencies();

        return 0;
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

        $amount              = bcmul('-1', $source->amount);

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
        if($this->isForeignCurrencyTransfer($journal)) {
            Log::debug(sprintf('Can skip foreign currency transfer #%d.', $journal->id));
            return;
        }

        $message = sprintf('Sum of journal #%d is not zero, journal is broken and now fixed.', $journal->id);

        $this->friendlyWarning($message);
        app('log')->warning($message);

        $destination->amount = $amount;
        $destination->save();

        $message             = sprintf('Corrected amount in transaction journal #%d', $param);
        $this->friendlyInfo($message);
        ++$this->count;
    }

    private function fixUnevenAmounts(): void
    {
        $journals = \DB::table('transactions')
            ->groupBy('transaction_journal_id')
            ->whereNull('deleted_at')
            ->get(['transaction_journal_id', \DB::raw('SUM(amount) AS the_sum')])
        ;

        /** @var \stdClass $entry */
        foreach ($journals as $entry) {
            $sum = (string) $entry->the_sum;
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
            } catch (\ValueError $e) {
                $this->friendlyError(sprintf('Could not bccomp("%s", "0").', $sum));
                Log::error($e->getMessage());
                Log::error($e->getTraceAsString());
            }
            if (0 !== $res) {
                $this->fixJournal($entry->transaction_journal_id);
            }
        }
        if (0 === $this->count) {
            $this->friendlyPositive('Database amount integrity is OK');
        }
    }

    private function matchCurrencies(): void
    {
        $journals = TransactionJournal::leftJoin('transactions', 'transaction_journals.id', 'transactions.transaction_journal_id')
            ->where('transactions.transaction_currency_id', '!=', \DB::raw('transaction_journals.transaction_currency_id'))
            ->get(['transaction_journals.*']);

        $count = 0;
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            if(!$this->isForeignCurrencyTransfer($journal)) {
                Transaction::where('transaction_journal_id', $journal->id)->update(['transaction_currency_id' => $journal->transaction_currency_id]);
                $count++;
                continue;
            }
            Log::debug(sprintf('Can skip foreign currency transfer #%d.', $journal->id));
        }
        if (0 === $count) {
            $this->friendlyPositive('Journal currency integrity is OK');

            return;
        }

        $this->friendlyPositive(sprintf('Fixed %d journal(s) with mismatched currencies.', $journals->count()));
    }

    private function isForeignCurrencyTransfer(TransactionJournal $journal): bool
    {
        if(TransactionType::TRANSFER !== $journal->transactionType->type) {
            return false;
        }
        /** @var Transaction $destination */
        $destination         = $journal->transactions()->where('amount', '>', 0)->first();
        /** @var Transaction $source */
        $source         = $journal->transactions()->where('amount', '<', 0)->first();

        // safety catch on NULL should not be necessary, we just had that catch.
        // source amount = dest foreign amount
        // source currency = dest foreign currency
        // dest amount = source foreign currency
        // dest currency = source foreign currency

//        Log::debug(sprintf('[a] %s', bccomp(app('steam')->positive($source->amount), app('steam')->positive($destination->foreign_amount))));
//        Log::debug(sprintf('[b] %s', bccomp(app('steam')->positive($destination->amount), app('steam')->positive($source->foreign_amount))));
//        Log::debug(sprintf('[c] %s', var_export($source->transaction_currency_id === $destination->foreign_currency_id,true)));
//        Log::debug(sprintf('[d] %s', var_export((int) $destination->transaction_currency_id ===(int)  $source->foreign_currency_id, true)));

        if(0 === bccomp(app('steam')->positive($source->amount), app('steam')->positive($destination->foreign_amount)) &&
            $source->transaction_currency_id === $destination->foreign_currency_id &&
           0 === bccomp(app('steam')->positive($destination->amount), app('steam')->positive($source->foreign_amount)) &&
           (int) $destination->transaction_currency_id === (int) $source->foreign_currency_id
        ) {
            return true;
        }
        return false;

    }
}
