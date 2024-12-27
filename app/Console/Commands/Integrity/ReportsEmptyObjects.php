<?php

/**
 * ReportEmptyObjects.php
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

namespace FireflyIII\Console\Commands\Integrity;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use Illuminate\Console\Command;

class ReportsEmptyObjects extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Reports on empty database objects.';

    protected $signature   = 'integrity:empty-objects';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->reportEmptyBudgets();
        $this->reportEmptyCategories();
        $this->reportEmptyTags();
        $this->reportAccounts();
        $this->reportBudgetLimits();

        return 0;
    }

    /**
     * Report on budgets with no transactions or journals.
     */
    private function reportEmptyBudgets(): void
    {
        $set = Budget::leftJoin('budget_transaction_journal', 'budgets.id', '=', 'budget_transaction_journal.budget_id')
            ->leftJoin('users', 'budgets.user_id', '=', 'users.id')
            ->distinct()
            ->whereNull('budget_transaction_journal.budget_id')
            ->whereNull('budgets.deleted_at')
            ->get(['budgets.id', 'budgets.name', 'budgets.user_id', 'users.email'])
        ;

        /** @var \stdClass $entry */
        foreach ($set as $entry) {
            $line = sprintf(
                'User #%d (%s) has budget #%d ("%s") which has no transaction journals.',
                $entry->user_id,
                $entry->email,
                $entry->id,
                $entry->name
            );
            $this->friendlyWarning($line);
        }
    }

    /**
     * Report on categories with no transactions or journals.
     */
    private function reportEmptyCategories(): void
    {
        $set = Category::leftJoin('category_transaction_journal', 'categories.id', '=', 'category_transaction_journal.category_id')
            ->leftJoin('users', 'categories.user_id', '=', 'users.id')
            ->distinct()
            ->whereNull('category_transaction_journal.category_id')
            ->whereNull('categories.deleted_at')
            ->get(['categories.id', 'categories.name', 'categories.user_id', 'users.email'])
        ;

        /** @var \stdClass $entry */
        foreach ($set as $entry) {
            $line = sprintf(
                'User #%d (%s) has category #%d ("%s") which has no transaction journals.',
                $entry->user_id,
                $entry->email,
                $entry->id,
                $entry->name
            );
            $this->friendlyWarning($line);
        }
    }

    private function reportEmptyTags(): void
    {
        $set = Tag::leftJoin('tag_transaction_journal', 'tags.id', '=', 'tag_transaction_journal.tag_id')
            ->leftJoin('users', 'tags.user_id', '=', 'users.id')
            ->distinct()
            ->whereNull('tag_transaction_journal.tag_id')
            ->whereNull('tags.deleted_at')
            ->get(['tags.id', 'tags.tag', 'tags.user_id', 'users.email'])
        ;

        /** @var \stdClass $entry */
        foreach ($set as $entry) {
            $line = sprintf(
                'User #%d (%s) has tag #%d ("%s") which has no transaction journals.',
                $entry->user_id,
                $entry->email,
                $entry->id,
                $entry->tag
            );
            $this->friendlyWarning($line);
        }
    }

    /**
     * Reports on accounts with no transactions.
     */
    private function reportAccounts(): void
    {
        $set = Account::leftJoin('transactions', 'transactions.account_id', '=', 'accounts.id')
            ->leftJoin('users', 'accounts.user_id', '=', 'users.id')
            ->groupBy(['accounts.id', 'accounts.encrypted', 'accounts.name', 'accounts.user_id', 'users.email'])
            ->whereNull('transactions.account_id')
            ->get(
                ['accounts.id', 'accounts.encrypted', 'accounts.name', 'accounts.user_id', 'users.email']
            )
        ;

        /** @var \stdClass $entry */
        foreach ($set as $entry) {
            $line = 'User #%d (%s) has account #%d ("%s") which has no transactions.';
            $line = sprintf($line, $entry->user_id, $entry->email, $entry->id, $entry->name);
            $this->friendlyWarning($line);
        }
    }

    /**
     * Reports on budgets with no budget limits (which makes them pointless).
     */
    private function reportBudgetLimits(): void
    {
        $set = Budget::leftJoin('budget_limits', 'budget_limits.budget_id', '=', 'budgets.id')
            ->leftJoin('users', 'budgets.user_id', '=', 'users.id')
            ->groupBy(['budgets.id', 'budgets.name', 'budgets.encrypted', 'budgets.user_id', 'users.email'])
            ->whereNull('budget_limits.id')
            ->get(['budgets.id', 'budgets.name', 'budgets.user_id', 'budgets.encrypted', 'users.email'])
        ;

        /** @var Budget $entry */
        foreach ($set as $entry) {
            $line = sprintf(
                'User #%d (%s) has budget #%d ("%s") which has no budget limits.',
                $entry->user_id,
                $entry->email,
                $entry->id,
                $entry->name
            );
            $this->friendlyWarning($line);
        }
    }
}
