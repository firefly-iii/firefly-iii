<?php

/**
 * FixPiggies.php
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
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Console\Command;

/**
 * Report (and fix) piggy banks.
 *
 * Class FixPiggies
 */
class CorrectsPiggyBanks extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Fixes common issues with piggy banks.';
    protected $signature   = 'correction:piggy-banks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = 0;
        $set   = PiggyBankEvent::with(['PiggyBank', 'TransactionJournal'])->get();

        /** @var PiggyBankEvent $event */
        foreach ($set as $event) {
            if (null === $event->transaction_journal_id) {
                continue;
            }

            /** @var null|TransactionJournal $journal */
            $journal = $event->transactionJournal;

            if (null === $journal) {
                $event->transaction_journal_id = null;
                $event->save();
                ++$count;

                continue;
            }
        }
        if (0 !== $count) {
            $this->friendlyInfo(sprintf('Fixed %d piggy bank event(s).', $count));
        }

        return 0;
    }
}
