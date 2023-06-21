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

use DB;
use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Console\Command;
use stdClass;

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
     *
     * @return int
     */
    public function handle(): int
    {
        $count    = 0;
        $journals = DB::table('transactions')
                      ->groupBy('transaction_journal_id')
                      ->whereNull('deleted_at')
                      ->get(['transaction_journal_id', DB::raw('SUM(amount) AS the_sum')]);
        /** @var stdClass $entry */
        foreach ($journals as $entry) {
            $sum = (string)$entry->the_sum;
            if (!is_numeric($sum) || '' === $sum || str_contains($sum, 'e') || str_contains($sum, ',')) {
                $message = sprintf(
                    'Journal #%d has an invalid sum ("%s"). No sure what to do.',
                    $entry->transaction_journal_id,
                    $entry->the_sum
                );
                $this->friendlyWarning($message);
                app('log')->warning($message);
                $count++;
                continue;
            }
            if (0 !== bccomp((string)$entry->the_sum, '0')) {
                $message = sprintf(
                    'Sum of journal #%d is %s instead of zero.',
                    $entry->transaction_journal_id,
                    $entry->the_sum
                );
                $this->friendlyWarning($message);
                app('log')->warning($message);
                $this->fixJournal((int)$entry->transaction_journal_id);
                $count++;
            }
        }
        if (0 === $count) {
            $this->friendlyPositive('Database amount integrity is OK');
        }

        return 0;
    }

    /**
     * @param int $param
     */
    private function fixJournal(int $param): void
    {
        // one of the transactions is bad.
        $journal = TransactionJournal::find($param);
        if (!$journal) {
            return;
        }
        /** @var Transaction|null $source */
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
            TransactionJournal::where('id', $journal->description ?? 0)->forceDelete();

            return;
        }

        $amount = bcmul('-1', (string)$source->amount);

        // fix amount of destination:
        /** @var Transaction|null $destination */
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
            TransactionJournal::where('id', $journal->description ?? 0)->forceDelete();

            return;
        }

        $destination->amount = $amount;
        $destination->save();

        $message = sprintf('Corrected amount in transaction journal #%d', $param);
        $this->friendlyInfo($message);
    }
}
