<?php
/**
 * FixPiggies.php
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

use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Console\Command;

/**
 * Report (and fix) piggy banks. Make sure there are only transfers linked to piggy bank events.
 *
 * Class FixPiggies
 */
class FixPiggies extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes common issues with piggy banks.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:fix-piggies';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): int
    {
        $set = PiggyBankEvent::with(['PiggyBank', 'TransactionJournal', 'TransactionJournal.TransactionType'])->get();
        $set->each(
            function (PiggyBankEvent $event) {
                if (null === $event->transaction_journal_id) {
                    return true;
                }
                /** @var TransactionJournal $journal */
                $journal = $event->transactionJournal()->first();
                if (null === $journal) {
                    return true;
                }

                $type = $journal->transactionType->type;
                if (TransactionType::TRANSFER !== $type) {
                    $event->transaction_journal_id = null;
                    $event->save();
                    $this->line(sprintf('Piggy bank #%d was referenced by an invalid event. This has been fixed.', $event->piggy_bank_id));
                }

                return true;
            }
        );
        $this->line(sprintf('Verified the content of %d piggy bank events.', $set->count()));

        return 0;
    }
}
