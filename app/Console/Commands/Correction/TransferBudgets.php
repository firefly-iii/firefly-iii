<?php
/**
 * TransferBudgets.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Console\Command;

/**
 * Class TransferBudgets
 */
class TransferBudgets extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes budgets from transfers.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:fix-transfer-budgets';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $start = microtime(true);
        $set   = TransactionJournal::distinct()
                                   ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                   ->leftJoin('budget_transaction_journal', 'transaction_journals.id', '=', 'budget_transaction_journal.transaction_journal_id')
                                   ->whereNotIn('transaction_types.type', [TransactionType::WITHDRAWAL])
                                   ->whereNotNull('budget_transaction_journal.budget_id')->get(['transaction_journals.*']);
        $count = 0;
        /** @var TransactionJournal $entry */
        foreach ($set as $entry) {
            $this->info(sprintf('Transaction journal #%d is a %s, so has no longer a budget.', $entry->id, $entry->transactionType->type));
            $entry->budgets()->sync([]);
            $count++;
        }
        if (0 === $count) {
            $this->info('No invalid budget/journal entries.');
        }
        if (0 !== $count) {
            $this->line(sprintf('Corrected %d invalid budget/journal entries (entry).', $count));
        }
        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Verified budget/journals in %s seconds.', $end));

        return 0;
    }
}
