<?php

/**
 * RemoveBills.php
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
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Console\Command;

/**
 * Class RemoveBills
 */
class RemovesBills extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Remove bills from transactions that shouldn\'t have one.';
    protected $signature   = 'correction:bills';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var null|TransactionType $withdrawal */
        $withdrawal = TransactionType::where('type', TransactionType::WITHDRAWAL)->first();
        if (null === $withdrawal) {
            return 0;
        }
        $journals   = TransactionJournal::whereNotNull('bill_id')->where('transaction_type_id', '!=', $withdrawal->id)->get();

        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $this->friendlyWarning(sprintf('Transaction journal #%d will be unlinked from bill #%d.', $journal->id, $journal->bill_id));
            $journal->bill_id = null;
            $journal->save();
        }
        if ($journals->count() > 0) {
            $this->friendlyInfo('Fixed all transaction journals so they have correct bill information.');
        }

        return 0;
    }
}
