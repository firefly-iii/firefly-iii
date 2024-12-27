<?php

/**
 * DeleteZeroAmount.php
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

/**
 * Class DeleteZeroAmount
 */
class DeletesZeroAmount extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Delete transactions with zero amount.';

    protected $signature   = 'firefly-iii:delete-zero-amount';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $set      = Transaction::where('amount', 0)->get(['transaction_journal_id'])->pluck('transaction_journal_id')->toArray();
        $set      = array_unique($set);
        $journals = TransactionJournal::whereIn('id', $set)->get();

        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $this->friendlyWarning(sprintf('Deleted transaction journal #%d because the amount is zero (0.00).', $journal->id));
            $journal->delete();

            Transaction::where('transaction_journal_id', $journal->id)->delete();
        }
        if (0 === $journals->count()) {
            $this->friendlyPositive('No zero-amount transaction journals.');
        }

        return 0;
    }
}
