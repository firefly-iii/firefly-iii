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

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->fixUnevenAmounts();
        $this->matchCurrencies();
        return 0;
    }

    private function fixJournal(int $param): void
    {
        // one of the transactions is bad.
        $journal = TransactionJournal::find($param);
        if (null === $journal) {
            return;
        }

        /** @var null|Transaction $source */
        $source = $journal->transactions()->where('amount', '<', 0)->first();

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

            return;
        }

        $amount = bcmul('-1', $source->amount);

        // fix amount of destination:
        /** @var null|Transaction $destination */
        $destination = $journal->transactions()->where('amount', '>', 0)->first();

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

            return;
        }

        $destination->amount = $amount;
        $destination->save();

        $message = sprintf('Corrected amount in transaction journal #%d', $param);
        $this->friendlyInfo($message);
    }

    private function fixUnevenAmounts(): void
    {
        $count    = 0;
        $journals = \DB::table('transactions')
                       ->groupBy('transaction_journal_id')
                       ->whereNull('deleted_at')
                       ->get(['transaction_journal_id', \DB::raw('SUM(amount) AS the_sum')]);

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
                ++$count;

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
                $message = sprintf(
                    'Sum of journal #%d is %s instead of zero.',
                    $entry->transaction_journal_id,
                    $entry->the_sum
                );
                $this->friendlyWarning($message);
                app('log')->warning($message);
                $this->fixJournal($entry->transaction_journal_id);
                ++$count;
            }
        }
        if (0 === $count) {
            $this->friendlyPositive('Database amount integrity is OK');
        }
    }

    private function matchCurrencies(): void
    {
        $journals = TransactionJournal
            ::leftJoin('transactions', 'transaction_journals.id',  'transactions.transaction_journal_id')
            ->where('transactions.transaction_currency_id', '!=', \DB::raw('transaction_journals.transaction_currency_id'))
            ->get(['transaction_journals.*']);
        if (0 === $journals->count()) {
            $this->friendlyPositive('Journal currency integrity is OK');
            return;
        }
        /** @var TransactionJournal $journal */
        foreach($journals as $journal) {
            Transaction::where('transaction_journal_id', $journal->id)->update(['transaction_currency_id' => $journal->transaction_currency_id]);
        }
        $this->friendlyPositive(sprintf('Fixed %d journal(s) with mismatched currencies.', $journals->count()));

    }
}
