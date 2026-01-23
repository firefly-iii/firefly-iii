<?php

declare(strict_types=1);

/*
 * CreatesPiggyBankEventForChangedAmount.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Listeners\Model\PiggyBank;

use FireflyIII\Events\Model\PiggyBank\PiggyBankAmountIsChanged;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\TransactionGroup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CreatesPiggyBankEventForChangedAmount implements ShouldQueue
{
    public function handle(PiggyBankAmountIsChanged $event): void
    {
        // find journal if group is present.
        $journal = $event->transactionJournal;
        if ($event->transactionGroup instanceof TransactionGroup) {
            $journal = $event->transactionGroup->transactionJournals()->first();
        }
        $date    = $journal->date ?? today(config('app.timezone'));
        // sanity check: event must not already exist for this journal and piggy bank.
        if (null !== $journal) {
            $exists = PiggyBankEvent::where('piggy_bank_id', $event->piggyBank->id)->where('transaction_journal_id', $journal->id)->exists();
            if ($exists) {
                Log::warning('Already have event for this journal and piggy, will not create another.');

                return;
            }
        }

        PiggyBankEvent::create([
            'piggy_bank_id'          => $event->piggyBank->id,
            'transaction_journal_id' => $journal?->id,
            'date'                   => $date->format('Y-m-d'),
            'date_tz'                => $date->format('e'),
            'amount'                 => $event->amount,
        ]);
    }
}
