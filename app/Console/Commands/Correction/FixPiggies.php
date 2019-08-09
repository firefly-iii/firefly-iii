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

    /** @var int */
    private $count;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): int
    {
        $this->count = 0;
        $start       = microtime(true);
        $set         = PiggyBankEvent::with(['PiggyBank', 'TransactionJournal', 'TransactionJournal.TransactionType'])->get();

        /** @var PiggyBankEvent $event */
        foreach ($set as $event) {

            if (null === $event->transaction_journal_id) {
                continue;
            }
            /** @var TransactionJournal $journal */
            $journal = $event->transactionJournal;
            // @codeCoverageIgnoreStart
            if (null === $journal) {
                $event->transaction_journal_id = null;
                $event->save();
                $this->count++;
                continue;
            }
            // @codeCoverageIgnoreEnd

            $type = $journal->transactionType->type;
            if (TransactionType::TRANSFER !== $type) {
                $event->transaction_journal_id = null;
                $event->save();
                $this->line(sprintf('Piggy bank #%d was referenced by an invalid event. This has been fixed.', $event->piggy_bank_id));
                $this->count++;
                continue;
            }
        }
        if (0 === $this->count) {
            $this->line('All piggy bank events are correct.');
        }
        if (0 !== $this->count) {
            $this->line(sprintf('Fixed %d piggy bank event(s).', $this->count));
        }

        $end = round(microtime(true) - $start, 2);
        $this->line(sprintf('Verified the content of %d piggy bank events in %s seconds.', $set->count(), $end));

        return 0;
    }
}
