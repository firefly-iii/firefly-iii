<?php
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
    public function handle()
    {
        // accounts
        // remove soft-deleted accounts from account_balances
        // remove soft-deleted accounts from account_meta
        // remove soft-deleted accounts from account_piggy_bank
        // remove soft-deleted accounts from attachments.

        // journals
        // remove soft-deleted journals from attachments
        // audit_log_entries
        $deleted = TransactionJournal::withTrashed()->whereNotNull('deleted_at')->get('transaction_journals.id')->pluck('id')->toArray();
        $count   = DB::table('tag_transaction_journal')->whereIn('transaction_journal_id', $deleted)->delete();
        if ($count > 0) {
            $this->friendlyInfo(sprintf('Removed %d old relationship(s) between tags and transactions.', $count));
        }
        unset($deleted);
        // budgets
        // from auto_budgets
        // from budget_limits
        $deleted = Budget::withTrashed()->whereNotNull('deleted_at')->get('budgets.id')->pluck('id')->toArray();
        $count   = DB::table('budget_transaction')->whereIn('budget_id', $deleted)->delete();
        if ($count > 0) {
            $this->friendlyInfo(sprintf('Removed %d old relationship(s) between budgets and transactions.', $count));
        }
        $count = DB::table('budget_transaction_journal')->whereIn('budget_id', $deleted)->delete();
        if ($count > 0) {
            $this->friendlyInfo(sprintf('Removed %d old relationship(s) between budgets and transactions.', $count));
        }
        unset($deleted);
        // -> category_transaction
        // -> category_transaction_journal
        $deleted = Category::withTrashed()->whereNotNull('deleted_at')->get('categories.id')->pluck('id')->toArray();
        $count   = DB::table('category_transaction')->whereIn('category_id', $deleted)->delete();
        if ($count > 0) {
            $this->friendlyInfo(sprintf('Removed %d old relationship(s) between categories and transactions.', $count));
        }
        $count = DB::table('category_transaction_journal')->whereIn('category_id', $deleted)->delete();
        if ($count > 0) {
            $this->friendlyInfo(sprintf('Removed %d old relationship(s) categories budgets and transactions.', $count));
        }
        $this->friendlyNeutral('Validated links to deleted objects.');


    }
}
