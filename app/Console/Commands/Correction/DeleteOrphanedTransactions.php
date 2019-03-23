<?php
/**
 * DeleteOrphanedTransactions.php
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

use Exception;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Console\Command;
use stdClass;

/**
 * Deletes transactions where the journal has been deleted.
 */
class DeleteOrphanedTransactions extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes orphaned transactions.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:delete-orphaned-transactions';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws Exception
     */
    public function handle(): int
    {
        $this->deleteOrphanedTransactions();
        $this->deleteFromOrphanedAccounts();


        return 0;
    }

    /**
     *
     */
    private function deleteFromOrphanedAccounts(): void
    {
        $set
               = Transaction
            ::leftJoin('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->whereNotNull('accounts.deleted_at')
            ->get(['transactions.*']);
        $count = 0;
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            // delete journals
            $journal = TransactionJournal::find((int)$transaction->transaction_journal_id);
            if ($journal) {
                $journal->delete();
            }
            Transaction::where('transaction_journal_id', (int)$transaction->transaction_journal_id)->delete();
            $this->line(
                sprintf('Deleted transaction #%d because account #%d was already deleted.', $transaction->transaction_journal_id, $transaction->account_id)
            );
            $count++;
        }
        if(0===$count) {
            $this->info('No orphaned accounts.');
        }
    }

    /**
     * @throws Exception
     */
    private function deleteOrphanedTransactions(): void
    {
        $count = 0;
        $set   = Transaction
            ::leftJoin('transaction_journals', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->whereNotNull('transaction_journals.deleted_at')
            ->whereNull('transactions.deleted_at')
            ->whereNotNull('transactions.id')
            ->get(
                [
                    'transaction_journals.id as journal_id',
                    'transactions.id as transaction_id',
                ]
            );
        /** @var stdClass $entry */
        foreach ($set as $entry) {
            $transaction = Transaction::find((int)$entry->transaction_id);
            $transaction->delete();
            $this->info(
                sprintf(
                    'Transaction #%d (part of deleted journal #%d) has been deleted as well.',
                    $entry->transaction_id,
                    $entry->journal_id
                )
            );
            ++$count;
        }
        if (0 === $count) {
            $this->info('No orphaned transactions.');
        }
    }
}
