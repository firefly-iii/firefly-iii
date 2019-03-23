<?php
/**
 * DeleteZeroAmount.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Exception;

/**
 * Class DeleteZeroAmount
 */
class DeleteZeroAmount extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete transactions with zero amount.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:delete-zero-amount';

    /**
     * Execute the console command.
     * @throws Exception
     * @return int
     */
    public function handle(): int
    {
        $set = Transaction::where('amount', 0)->get(['transaction_journal_id'])->pluck('transaction_journal_id')->toArray();
        $set = array_unique($set);
        /** @var Collection $journals */
        $journals = TransactionJournal::whereIn('id', $set)->get();
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $this->info(sprintf('Deleted transaction #%d because the amount is zero (0.00).', $journal->id));
            $journal->delete();
            Transaction::where('transaction_journal_id', $journal->id)->delete();
        }
        if (0 === $journals->count()) {
            $this->info('No zero-amount transactions.');
        }

        return 0;
    }
}
