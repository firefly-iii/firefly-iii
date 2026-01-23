<?php

declare(strict_types=1);

/*
 * RemovesLinksToDeletedObjects.php
 * Copyright (c) 2025 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemovesLinksToDeletedObjects extends Command
{
    use ShowsFriendlyMessages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'correction:remove-links-to-deleted-objects';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes deleted entries from intermediate tables.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $deletedTags       = Tag::withTrashed()
            ->whereNotNull('deleted_at')
            ->get('tags.id')
            ->pluck('id')
            ->toArray();
        $deletedJournals   = TransactionJournal::withTrashed()
            ->whereNotNull('deleted_at')
            ->get('transaction_journals.id')
            ->pluck('id')
            ->toArray();
        $deletedBudgets    = Budget::withTrashed()
            ->whereNotNull('deleted_at')
            ->get('budgets.id')
            ->pluck('id')
            ->toArray();
        $deletedCategories = Category::withTrashed()
            ->whereNotNull('deleted_at')
            ->get('categories.id')
            ->pluck('id')
            ->toArray();

        if (count($deletedTags) > 0) {
            $this->cleanupTags($deletedTags);
        }
        if (count($deletedJournals) > 0) {
            $this->cleanupJournals($deletedJournals);
        }
        if (count($deletedBudgets) > 0) {
            $this->cleanupBudgets($deletedBudgets);
        }
        if (count($deletedCategories) > 0) {
            $this->cleanupCategories($deletedCategories);
        }
        $this->friendlyNeutral('Validated links to deleted objects.');
    }

    private function cleanupTags(array $tags): void
    {
        $count = DB::table('tag_transaction_journal')->whereIn('tag_id', $tags)->delete();
        if ($count > 0) {
            $this->friendlyInfo(sprintf('Removed %d old relationship(s) categories transactions and tags.', $count));
        }
    }

    private function cleanupJournals(array $journals): void
    {
        $countTags       = 0;
        $countBudgets    = 0;
        $countCategories = 0;
        // #11333
        foreach (array_chunk($journals, 1337) as $set) {
            $countTags       += DB::table('tag_transaction_journal')->whereIn('transaction_journal_id', $set)->delete();
            $countBudgets    += DB::table('budget_transaction_journal')->whereIn('transaction_journal_id', $set)->delete();
            $countCategories += DB::table('category_transaction_journal')->whereIn('transaction_journal_id', $set)->delete();
        }

        if ($countTags > 0) {
            $this->friendlyInfo(sprintf('Removed %d old relationship(s) between tags and transactions.', $countTags));
        }

        if ($countBudgets > 0) {
            $this->friendlyInfo(sprintf('Removed %d old relationship(s) between budgets and transactions.', $countBudgets));
        }
        if ($countCategories > 0) {
            $this->friendlyInfo(sprintf('Removed %d old relationship(s) categories and transactions.', $countCategories));
        }
    }

    private function cleanupBudgets(array $budgets): void
    {
        $count = DB::table('budget_transaction_journal')->whereIn('budget_id', $budgets)->delete();
        if ($count > 0) {
            $this->friendlyInfo(sprintf('Removed %d old relationship(s) between budgets and transactions.', $count));
        }
    }

    private function cleanupCategories(array $categories): void
    {
        $count = DB::table('category_transaction_journal')->whereIn('category_id', $categories)->delete();
        if ($count > 0) {
            $this->friendlyInfo(sprintf('Removed %d old relationship(s) categories categories and transactions.', $count));
        }
    }
}
