<?php

declare(strict_types=1);

/*
 * RecalculatesPrimaryAmounts.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Services\Internal\Recalculate;

use FireflyIII\Events\Model\Account\UpdatedExistingAccount;
use FireflyIII\Handlers\Observer\TransactionObserver;
use FireflyIII\Models\Account;
use FireflyIII\Models\AutoBudget;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\UserGroup\UserGroupRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\FireflyConfig;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrimaryAmountRecalculationService
{
    public function recalculate(): void
    {
        if (false === FireflyConfig::get('enable_exchange_rates', config('cer.enabled'))->data) {
            return;
        }

        /** @var UserGroupRepositoryInterface $repository */
        $repository = app(UserGroupRepositoryInterface::class);

        /** @var UserGroup $userGroup */
        foreach ($repository->getAll() as $userGroup) {
            Log::debug('Resetting primary currency amounts for all objects.');
            $this->resetGenericTables($userGroup);
            $this->resetPiggyBanks($userGroup);
            $this->resetBudgets($userGroup);
            $this->resetTransactions($userGroup);
            Log::debug('Have now reset all primary amounts to NULL.');
            $this->recalculateForGroup($userGroup);
        }
    }

    public function recalculateForGroup(UserGroup $userGroup): void
    {
        Log::debug(sprintf('Now recalculating primary amounts for user group #%d', $userGroup->id));

        // do a check with the group's currency so we can skip some stuff.
        $currency = Amount::getPrimaryCurrencyByUserGroup($userGroup);

        $this->recalculateAccounts($userGroup, $currency);
        $this->recalculatePiggyBanks($userGroup, $currency);
        $this->recalculateBudgets($userGroup, $currency);
        $this->recalculateAvailableBudgets($userGroup, $currency);
        $this->recalculateBills($userGroup, $currency);
        $this->calculateTransactions($userGroup, $currency);
    }

    public function recalculateForGroupAndCurrency(UserGroup $userGroup, TransactionCurrency $limitCurrency): void
    {
        // do a check with the group's currency so we can skip some stuff.
        $currency = Amount::getPrimaryCurrencyByUserGroup($userGroup);
        if ($limitCurrency->id === $currency->id) {
            Log::debug(sprintf('Can skip recalculation because user requested the same currencies (%s).', $limitCurrency->code));

            return;
        }

        $this->recalculateAccountsForCurrency($userGroup, $currency, $limitCurrency);
        $this->recalculatePiggyBanks($userGroup, $currency);
        $this->recalculateBudgets($userGroup, $currency);
        $this->recalculateAvailableBudgets($userGroup, $currency);
        $this->recalculateBills($userGroup, $currency);
        $this->calculateTransactionsForCurrency($userGroup, $currency, $limitCurrency);
    }

    private function calculateTransactions(UserGroup $userGroup, TransactionCurrency $currency): void
    {
        // custom query because of the potential size of this update.
        $set                              = DB::table('transactions')
            ->join('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.user_group_id', $userGroup->id)
            ->where(static function (DatabaseBuilder $q1) use ($currency): void {
                $q1->where(static function (DatabaseBuilder $q2) use ($currency): void {
                    $q2->whereNot('transactions.transaction_currency_id', $currency->id)->whereNull('transactions.foreign_currency_id');
                })->orWhere(static function (DatabaseBuilder $q3) use ($currency): void {
                    $q3->whereNot('transactions.transaction_currency_id', $currency->id)->whereNot('transactions.foreign_currency_id', $currency->id);
                });
            })
            //            ->where(static function (DatabaseBuilder $q) use ($currency): void {
            //                $q->whereNot('transactions.transaction_currency_id', $currency->id)
            //                    ->whereNot('transactions.foreign_currency_id', $currency->id)
            //                ;
            //            })
            ->get(['transactions.id'])
        ;
        TransactionObserver::$recalculate = false;
        Log::debug(sprintf('Count of set is %d', $set->count()));
        foreach ($set as $item) {
            Log::debug(sprintf('Touch transaction #%d', $item->id));

            // here we are.
            /** @var null|Transaction $transaction */
            $transaction = Transaction::find($item->id);
            $transaction?->touch();
        }
        TransactionObserver::$recalculate = true;
        Log::debug(sprintf('Recalculated %d transactions.', $set->count()));
    }

    private function calculateTransactionsForCurrency(UserGroup $userGroup, TransactionCurrency $currency, TransactionCurrency $limitCurrency): void
    {
        Log::debug(sprintf('Now in calculateTransactionsForCurrency(#%d, %s, %s)', $userGroup->id, $currency->code, $limitCurrency->code));
        // custom query because of the potential size of this update.
        $set                              = DB::table('transactions')
            ->join('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.user_group_id', $userGroup->id)
            ->where(static function (DatabaseBuilder $q1) use ($currency): void {
                $q1->where(static function (DatabaseBuilder $q2) use ($currency): void {
                    $q2->whereNot('transactions.transaction_currency_id', $currency->id)->whereNull('transactions.foreign_currency_id');
                })->orWhere(static function (DatabaseBuilder $q3) use ($currency): void {
                    $q3->whereNot('transactions.transaction_currency_id', $currency->id)->whereNot('transactions.foreign_currency_id', $currency->id);
                });
            })
            // must be in the limit currency.
            ->where('transactions.transaction_currency_id', $limitCurrency->id)
            ->orWhere('transactions.foreign_currency_id', $limitCurrency->id)
            ->get(['transactions.id'])
        ;
        TransactionObserver::$recalculate = false;
        Log::debug(sprintf('Count of set is %d', $set->count()));
        foreach ($set as $item) {
            Log::debug(sprintf('Touch transaction #%d', $item->id));

            // here we are.
            /** @var null|Transaction $transaction */
            $transaction = Transaction::find($item->id);
            $transaction?->touch();
        }
        TransactionObserver::$recalculate = true;
        Log::debug(sprintf('Recalculated %d transactions.', $set->count()));
    }

    private function collectAccounts(UserGroup $userGroup): Collection
    {
        return $userGroup
            ->accounts()
            ->where(static function (EloquentBuilder $q): void {
                $q->whereNotNull('virtual_balance');

                // this needs a different piece of code for postgres.
                if ('pgsql' === config('database.default')) {
                    $q->orWhere(DB::raw('CAST(virtual_balance AS TEXT)'), '!=', '');
                }
                if ('pgsql' !== config('database.default')) {
                    $q->orWhere('virtual_balance', '!=', '');
                }
            })
            ->get()
        ;
    }

    /**
     * Only recalculate accounts that have a virtual balance.
     */
    private function recalculateAccounts(UserGroup $userGroup, TransactionCurrency $groupCurrency): void
    {
        Log::debug(sprintf('recalculateAccounts(#%d, %s)', $userGroup->id, $groupCurrency->code));
        $set = $this->collectAccounts($userGroup);

        /** @var Account $account */
        foreach ($set as $account) {
            $currencyId = (int) $account->accountMeta()->where('name', 'currency_id')->first()?->data;
            if ($groupCurrency->id === $currencyId) {
                Log::debug(sprintf('Account "%s" is in group currency %s. Skip.', $account->name, $groupCurrency->code));

                continue;
            }
            Log::debug(sprintf('Account "%s" is NOT in group currency %s, so do it.', $account->name, $groupCurrency->code));
            $account->touch();
        }
        Log::debug(sprintf('Recalculated %d accounts for user group #%d.', $set->count(), $userGroup->id));
    }

    /**
     * Only recalculate accounts that have a virtual balance.
     */
    private function recalculateAccountsForCurrency(UserGroup $userGroup, TransactionCurrency $groupCurrency, TransactionCurrency $limitCurrency): void
    {
        Log::debug(sprintf('recalculateAccountsForCurrency(#%d, %s, %s)', $userGroup->id, $groupCurrency->code, $limitCurrency->code));

        $set = $this->collectAccounts($userGroup);

        /** @var Account $account */
        foreach ($set as $account) {
            $currencyId = (int) $account->accountMeta()->where('name', 'currency_id')->first()->data;
            if ($groupCurrency->id === $currencyId) {
                Log::debug(sprintf('Account "%s" is in group currency %s. Skip.', $account->name, $groupCurrency->code));

                continue;
            }
            if ($limitCurrency->id !== $currencyId) {
                Log::debug(sprintf('Account "%s" is NOT in limit currency %s, skip.', $account->name, $limitCurrency->code));

                continue;
            }
            Log::debug(sprintf('Account "%s" is NOT in group currency %s, so do it.', $account->name, $groupCurrency->code));
            // TODO it is bad form to call an event from an event but OK.
            event(new UpdatedExistingAccount($account, []));
        }
        Log::debug(sprintf('Recalculated %d accounts for user group #%d.', $set->count(), $userGroup->id));
    }

    private function recalculateAutoBudgets(Budget $budget, TransactionCurrency $currency): void
    {
        $set = $budget->autoBudgets()->where('transaction_currency_id', '!=', $currency->id)->get();

        /** @var AutoBudget $autoBudget */
        foreach ($set as $autoBudget) {
            $autoBudget->touch();
        }
        Log::debug(sprintf('Recalculated %d auto budgets for budget #%d.', $set->count(), $budget->id));
    }

    private function recalculateAvailableBudgets(UserGroup $userGroup, TransactionCurrency $currency): void
    {
        Log::debug('Start with available budgets.');
        $set = $userGroup->availableBudgets()->where('transaction_currency_id', '!=', $currency->id)->get();

        /** @var AvailableBudget $budget */
        foreach ($set as $budget) {
            $budget->touch();
        }
        Log::debug(sprintf('Recalculated %d available budgets.', $set->count()));
    }

    private function recalculateBills(UserGroup $userGroup, TransactionCurrency $currency): void
    {
        $set = $userGroup->bills()->where('transaction_currency_id', '!=', $currency->id)->get();

        /** @var Bill $bill */
        foreach ($set as $bill) {
            $bill->touch();
        }
        Log::debug(sprintf('Recalculated %d bills.', $set->count()));
    }

    private function recalculateBudgetLimits(Budget $budget, TransactionCurrency $currency): void
    {
        $set = $budget->budgetlimits()->where('transaction_currency_id', '!=', $currency->id)->get();

        /** @var BudgetLimit $limit */
        foreach ($set as $limit) {
            Log::debug(sprintf('Will now touch BL #%d', $limit->id));
            $limit->touch();
            Log::debug(sprintf('Done with touch BL #%d', $limit->id));
        }
        Log::debug(sprintf('Recalculated %d budget limits for budget #%d.', $set->count(), $budget->id));
    }

    private function recalculateBudgets(UserGroup $userGroup, TransactionCurrency $currency): void
    {
        $set = $userGroup->budgets()->get();

        /** @var Budget $budget */
        foreach ($set as $budget) {
            $this->recalculateBudgetLimits($budget, $currency);
            $this->recalculateAutoBudgets($budget, $currency);
        }
        Log::debug(sprintf('Recalculated %d budgets.', $set->count()));
    }

    private function recalculatePiggyBankEvents(PiggyBank $piggyBank): void
    {
        $set = $piggyBank->piggyBankEvents()->get();
        $set->each(static function (PiggyBankEvent $event): void {
            $event->touch();
        });
        Log::debug(sprintf('Recalculated %d piggy bank events.', $set->count()));
    }

    /**
     * This method collects ALL piggy banks, but only processes those that do not have the userGroup's primary currency.
     */
    private function recalculatePiggyBanks(UserGroup $userGroup, TransactionCurrency $currency): void
    {
        $converter  = new ExchangeRateConverter();
        $converter->setUserGroup($userGroup);
        $converter->setIgnoreSettings(true);
        $repository = app(PiggyBankRepositoryInterface::class);
        $repository->setUserGroup($userGroup);
        $set        = $repository->getPiggyBanks();
        $set        = $set->filter(static fn (PiggyBank $piggyBank): bool => $currency->id !== $piggyBank->transaction_currency_id);
        foreach ($set as $piggyBank) {
            $piggyBank->encrypted = false;
            $piggyBank->save();

            foreach ($piggyBank->accounts as $account) {
                $account->pivot->native_current_amount = null;
                if (0 !== bccomp((string) $account->pivot->current_amount, '0')) {
                    $account->pivot->native_current_amount = $converter->convert(
                        $piggyBank->transactionCurrency,
                        $currency,
                        today(),
                        (string) $account->pivot->current_amount
                    );
                }
                $account->pivot->save();
            }
            $this->recalculatePiggyBankEvents($piggyBank);
        }
        Log::debug(sprintf('Recalculated %d piggy banks for user group #%d.', $set->count(), $userGroup->id));
    }

    private function resetBudget(Budget $budget): void
    {
        foreach ($budget->autoBudgets as $autoBudget) {
            if ('' === (string) $autoBudget->native_amount) {
                continue;
            }
            Log::debug(sprintf('Resetting native_amount for budget #%d and auto budget #%d.', $budget->id, $autoBudget->id));
            $autoBudget->native_amount = null;
            $autoBudget->saveQuietly();
        }
        foreach ($budget->budgetlimits as $limit) {
            if ('' !== (string) $limit->native_amount) {
                Log::debug(sprintf('Resetting native_amount for budget #%d and budget limit #%d.', $budget->id, $limit->id));
                $limit->native_amount = null;
                $limit->saveQuietly();
            }
        }
    }

    private function resetBudgets(UserGroup $userGroup): void
    {
        $repository = app(BudgetRepositoryInterface::class);
        $repository->setUserGroup($userGroup);
        $set        = $repository->getBudgets();

        Log::debug(sprintf('Reset primary currency of %d budget(s).', $set->count()));

        /** @var Budget $budget */
        foreach ($set as $budget) {
            $this->resetBudget($budget);
        }
    }

    private function resetGenericTables(UserGroup $userGroup): void
    {
        $tables = [
            // !!! this array is also in the migration
            'accounts'          => ['native_virtual_balance'],
            'available_budgets' => ['native_amount'],
            'bills'             => ['native_amount_min', 'native_amount_max'],
        ];
        foreach ($tables as $table => $columns) {
            Log::debug(sprintf('Now processing table "%s"', $table));
            foreach ($columns as $column) {
                Log::debug(sprintf('Resetting column "%s" in table "%s".', $column, $table));
                DB::table($table)->where('user_group_id', $userGroup->id)->update([$column => null]);
            }
        }
    }

    private function resetPiggyBank(PiggyBank $piggyBank): void
    {
        if ('' !== (string) $piggyBank->native_target_amount) {
            Log::debug(sprintf('Resetting native_target_amount for piggy bank #%d.', $piggyBank->id));
            $piggyBank->native_target_amount = null;
            $piggyBank->saveQuietly();
        }
        foreach ($piggyBank->accounts as $account) {
            if ('' !== (string) $account->pivot->native_current_amount) {
                Log::debug(sprintf('Resetting native_current_amount for piggy bank #%d and account #%d.', $piggyBank->id, $account->id));
                $account->pivot->native_current_amount = null;
                $account->pivot->save();
            }
        }
        foreach ($piggyBank->piggyBankEvents as $event) {
            if ('' !== (string) $event->native_amount) {
                Log::debug(sprintf('Resetting native_amount for piggy bank #%d and event #%d.', $piggyBank->id, $event->id));
                $event->native_amount = null;
                $event->saveQuietly();
            }
        }
    }

    private function resetPiggyBanks(UserGroup $userGroup): void
    {
        $repository = app(PiggyBankRepositoryInterface::class);
        $repository->setUserGroup($userGroup);
        $piggyBanks = $repository->getPiggyBanks();
        Log::debug(sprintf('Reset primary currency of %d piggy bank(s).', $piggyBanks->count()));

        /** @var PiggyBank $piggyBank */
        foreach ($piggyBanks as $piggyBank) {
            $this->resetPiggyBank($piggyBank);
        }
    }

    private function resetTransactions(UserGroup $userGroup): void
    {
        // custom query because of the potential size of this update.
        $success = DB::table('transactions')
            ->join('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.user_group_id', $userGroup->id)
            ->where(static function (Builder $q): void {
                $q->whereNotNull('native_amount')->orWhereNotNull('native_foreign_amount');
            })
            ->update(['native_amount' => null, 'native_foreign_amount' => null])
        ;
        Log::debug(sprintf('Reset %d transactions.', $success));
    }
}
