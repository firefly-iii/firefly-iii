<?php

/**
 * TransferBudgets.php
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

class CorrectsTransferBudgets extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Removes budgets from transfers.';
    protected $signature   = 'correction:transfer-budgets';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $set   = TransactionJournal::distinct()
            ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->leftJoin('budget_transaction_journal', 'transaction_journals.id', '=', 'budget_transaction_journal.transaction_journal_id')
            ->whereNotIn('transaction_types.type', [TransactionType::WITHDRAWAL])
            ->whereNotNull('budget_transaction_journal.budget_id')->get(['transaction_journals.*'])
        ;
        $count = 0;

        /** @var TransactionJournal $entry */
        foreach ($set as $entry) {
            $message = sprintf('Transaction journal #%d is a %s, so has no longer a budget.', $entry->id, $entry->transactionType->type);
            $this->friendlyInfo($message);
            app('log')->debug($message);
            $entry->budgets()->sync([]);
            ++$count;
        }
        if (0 !== $count) {
            $message = sprintf('Corrected %d invalid budget/journal entries (entry).', $count);
            app('log')->debug($message);
            $this->friendlyInfo($message);
        }

        return 0;
    }
}
