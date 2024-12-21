<?php
/*
 * PreferencesEventHandler.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\Preferences\UserGroupChangedDefaultCurrency;
use FireflyIII\Models\Budget;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\UserGroups\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\UserGroups\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PreferencesEventHandler
{
    public function resetNativeAmounts(UserGroupChangedDefaultCurrency $event): void
    {
        // Reset the native amounts for all objects that have it.
        Log::debug('Resetting native amounts for all objects.');

        $tables = [
            // !!! this array is also in the migration
            'accounts'          => ['native_virtual_balance'],
            'available_budgets' => ['native_amount'],
            'bills'             => ['native_amount_min', 'native_amount_max'],
            //'transactions' => ['native_amount', 'native_foreign_amount']
        ];
        foreach ($tables as $table => $columns) {
            foreach ($columns as $column) {
                Log::debug(sprintf('Resetting column %s in table %s.', $column, $table));
                DB::table($table)->where('user_group_id', $event->userGroup->id)->update([$column => null]);
            }
        }
        $this->resetPiggyBanks($event->userGroup);
        $this->resetBudgets($event->userGroup);
        $this->resetTransactions($event->userGroup);
    }

    private function resetPiggyBanks(UserGroup $userGroup): void
    {
        $repository = app(PiggyBankRepositoryInterface::class);
        $repository->setUserGroup($userGroup);
        $piggyBanks = $repository->getPiggyBanks();
        /** @var PiggyBank $piggyBank */
        foreach ($piggyBanks as $piggyBank) {
            if (null !== $piggyBank->native_target_amount) {
                Log::debug(sprintf('Resetting native_target_amount for piggy bank #%d.', $piggyBank->id));
                $piggyBank->native_target_amount = null;
                $piggyBank->saveQuietly();
            }
            foreach ($piggyBank->accounts as $account) {
                if (null !== $account->pivot->native_current_amount) {
                    Log::debug(sprintf('Resetting native_current_amount for piggy bank #%d and account #%d.', $piggyBank->id, $account->id));
                    $account->pivot->native_current_amount = null;
                    $account->pivot->save();
                }
            }
            foreach ($piggyBank->piggyBankEvents as $event) {
                if (null !== $event->native_amount) {
                    Log::debug(sprintf('Resetting native_amount for piggy bank #%d and event #%d.', $piggyBank->id, $event->id));
                    $event->native_amount = null;
                    $event->saveQuietly();
                }
            }
        }
    }

    private function resetBudgets(UserGroup $userGroup): void
    {
        $repository = app(BudgetRepositoryInterface::class);
        $repository->setUserGroup($userGroup);
        $set = $repository->getBudgets();
        /** @var Budget $budget */
        foreach ($set as $budget) {
            foreach ($budget->autoBudgets as $autoBudget) {
                if (null !== $autoBudget->native_amount) {
                    if (null !== $autoBudget->native_amount) {
                        Log::debug(sprintf('Resetting native_amount for budget #%d and auto budget #%d.', $budget->id, $autoBudget->id));
                        $autoBudget->native_amount = null;
                        $autoBudget->saveQuietly();
                    }
                }
            }
            foreach ($budget->budgetlimits as $limit) {
                if (null !== $limit->native_amount) {
                    Log::debug(sprintf('Resetting native_amount for budget #%d and budget limit #%d.', $budget->id, $limit->id));
                    $limit->native_amount = null;
                    $limit->saveQuietly();
                }
            }
        }

    }

    private function resetTransactions(UserGroup $userGroup): void
    {
        // custom query because of the potential size of this update.
        $success = DB::table('transactions')
          ->join('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
          ->where('transaction_journals.user_group_id', $userGroup->id)
          ->where(static function (Builder $q) {
              $q->whereNotNull('native_amount')
                ->orWhereNotNull('native_foreign_amount');
          })
          ->update(['native_amount' => null, 'native_foreign_amount' => null]);
        Log::debug(sprintf('Updated %d transactions.', $success));
    }
}
