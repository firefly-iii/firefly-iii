<?php
/**
 * BackToJournals.php
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

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Console\Command;

/**
 * Class BackToJournals
 */
class BackToJournals extends Command
{
    public const CONFIG_NAME = '4780_back_to_journals';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move meta data back to journals, not individual transactions.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:back-to-journals {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if (!$this->isMigrated()) {
            $this->error('Please run firefly-iii:migrate-to-groups first.');
        }
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->info('This command has been executed already.');

            return 0;
        }
        if (true === $this->option('force')) {
            $this->warn('Forcing the command.');
        }

        $this->migrateAll();

        $this->info('Updated category and budget info for all journals.');
        $this->markAsExecuted();

        return 0;
    }

    /**
     * @return bool
     */
    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool)$configVar->data;
        }

        return false; // @codeCoverageIgnore
    }

    /**
     * @return bool
     */
    private function isMigrated(): bool
    {
        $configVar = app('fireflyconfig')->get(MigrateToGroups::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool)$configVar->data;
        }

        return false; // @codeCoverageIgnore
    }

    /**
     *
     */
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }

    /**
     *
     */
    private function migrateAll(): void
    {
        $journals = TransactionJournal::get();
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $this->migrateCategoriesForJournal($journal);
            $this->migrateBudgetsForJournal($journal);
        }
    }

    /**
     * @param TransactionJournal $journal
     */
    private function migrateBudgetsForJournal(TransactionJournal $journal): void
    {
        // grab category from first transaction
        /** @var Transaction $transaction */
        $transaction = $journal->transactions()->first();
        /** @var Budget $budget */
        $budget = $transaction->budgets()->first();
        if (null !== $budget) {
            // sync to journal:
            $journal->budgets()->sync([(int)$budget->id]);

            // remove from transactions:
            $journal->transactions()->each(
                function (Transaction $transaction) {
                    $transaction->budgets()->sync([]);
                }
            );
        }
    }

    /**
     * @param TransactionJournal $journal
     */
    private function migrateCategoriesForJournal(TransactionJournal $journal): void
    {
        // grab category from first transaction
        /** @var Transaction $transaction */
        $transaction = $journal->transactions()->first();
        /** @var Category $category */
        $category = $transaction->categories()->first();
        if (null !== $category) {
            // sync to journal:
            $journal->categories()->sync([(int)$category->id]);

            // remove from transactions:
            $journal->transactions()->each(
                function (Transaction $transaction) {
                    $transaction->categories()->sync([]);
                }
            );
        }
    }
}
