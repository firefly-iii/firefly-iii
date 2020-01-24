<?php
/**
 * BackToJournals.php
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

namespace FireflyIII\Console\Commands\Upgrade;

use DB;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Log;

/**
 * Class BackToJournals
 */
class BackToJournals extends Command
{
    public const CONFIG_NAME = '480_back_to_journals';
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
        // @codeCoverageIgnoreStart
        $start = microtime(true);
        if (!$this->isMigrated()) {
            $this->error('Please run firefly-iii:migrate-to-groups first.');
        }
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->info('This command has already been executed.');

            return 0;
        }
        if (true === $this->option('force')) {
            $this->warn('Forcing the command.');
        }
        // @codeCoverageIgnoreEnd

        $this->migrateAll();
        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Updated category and budget info for all transaction journals in %s seconds.', $end));
        $this->markAsExecuted();

        return 0;
    }

    /**
     * @return array
     */
    private function getIdsForBudgets(): array
    {
        $transactions = DB::table('budget_transaction')->distinct()->get(['transaction_id'])->pluck('transaction_id')->toArray();
        $array        = [];
        $chunks       = array_chunk($transactions, 500);

        foreach ($chunks as $chunk) {
            $set = DB::table('transactions')
                     ->whereIn('transactions.id', $chunk)
                     ->get(['transaction_journal_id'])->pluck('transaction_journal_id')->toArray();
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $array = array_merge($array, $set);
        }

        return $array;
    }

    /**
     * @return array
     */
    private function getIdsForCategories(): array
    {
        $transactions = DB::table('category_transaction')->distinct()->get(['transaction_id'])->pluck('transaction_id')->toArray();
        $array        = [];
        $chunks       = array_chunk($transactions, 500);

        foreach ($chunks as $chunk) {
            $set   = DB::table('transactions')
                       ->whereIn('transactions.id', $chunk)
                       ->get(['transaction_journal_id'])->pluck('transaction_journal_id')->toArray();
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $array = array_merge($array, $set);
        }

        return $array;
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
        Log::debug('Now in migrateAll()');
        $this->migrateBudgets();
        $this->migrateCategories();

        // empty tables
        DB::table('budget_transaction')->delete();
        DB::table('category_transaction')->delete();
    }

    /**
     *
     */
    private function migrateBudgets(): void
    {
        $journals = new Collection;
        $allIds   = $this->getIdsForBudgets();
        $chunks   = array_chunk($allIds, 500);
        foreach ($chunks as $journalIds) {
            $collected = TransactionJournal::whereIn('id', $journalIds)->with(['transactions', 'budgets', 'transactions.budgets'])->get();
            $journals  = $journals->merge($collected);
        }
        $this->line(sprintf('Check %d transaction journal(s) for budget info.', count($journals)));
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
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
        $transaction = $journal->transactions->first();
        if (null === $transaction) {
            // @codeCoverageIgnoreStart
            $this->info(sprintf('Transaction journal #%d has no transactions. Will be fixed later.', $journal->id));

            return;
            // @codeCoverageIgnoreEnd
        }
        /** @var Budget $budget */
        $budget = $transaction->budgets->first();
        /** @var Budget $journalBudget */
        $journalBudget = $journal->budgets->first();

        // both have a budget, but they don't match.
        if (null !== $budget && null !== $journalBudget && $budget->id !== $journalBudget->id) {
            // sync to journal:
            $journal->budgets()->sync([(int)$budget->id]);

            return;
        }

        // transaction has a budget, but the journal doesn't.
        if (null !== $budget && null === $journalBudget) {
            // sync to journal:
            $journal->budgets()->sync([(int)$budget->id]);
        }
    }

    /**
     *
     */
    private function migrateCategories(): void
    {
        Log::debug('Now in migrateCategories()');
        $journals = new Collection;
        $allIds   = $this->getIdsForCategories();

        Log::debug(sprintf('Total: %d', count($allIds)));

        $chunks = array_chunk($allIds, 500);
        foreach ($chunks as $chunk) {
            Log::debug('Now doing a chunk.');
            $collected = TransactionJournal::whereIn('id', $chunk)->with(['transactions', 'categories', 'transactions.categories'])->get();
            $journals  = $journals->merge($collected);
        }
        $this->line(sprintf('Check %d transaction journal(s) for category info.', count($journals)));
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $this->migrateCategoriesForJournal($journal);
        }
    }

    /**
     * @param TransactionJournal $journal
     */
    private function migrateCategoriesForJournal(TransactionJournal $journal): void
    {
        // grab category from first transaction
        /** @var Transaction $transaction */
        $transaction = $journal->transactions->first();
        if (null === $transaction) {
            // @codeCoverageIgnoreStart
            $this->info(sprintf('Transaction journal #%d has no transactions. Will be fixed later.', $journal->id));

            return;
            // @codeCoverageIgnoreEnd
        }
        /** @var Category $category */
        $category = $transaction->categories->first();
        /** @var Category $journalCategory */
        $journalCategory = $journal->categories->first();

        // both have a category, but they don't match.
        if (null !== $category && null !== $journalCategory && $category->id !== $journalCategory->id) {
            // sync to journal:
            $journal->categories()->sync([(int)$category->id]);
        }

        // transaction has a category, but the journal doesn't.
        if (null !== $category && null === $journalCategory) {
            $journal->categories()->sync([(int)$category->id]);
        }
    }
}
