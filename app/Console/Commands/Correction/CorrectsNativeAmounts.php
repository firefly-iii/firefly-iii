<?php


/*
 * CorrectsNativeAmounts.php
 * Copyright (c) 2025 james@firefly-iii.org.
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
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
use FireflyIII\Repositories\UserGroup\UserGroupRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CorrectsNativeAmounts extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Recalculate native amounts for all objects.';

    protected $signature   = 'correction:recalculate-native-amounts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (false === config('cer.enabled')) {
            $this->friendlyInfo('This command will not run because currency exchange rates are disabled.');

            return 0;
        }
        Log::debug('Will update all native amounts. This may take some time.');
        $this->friendlyWarning('Recalculating native amounts for all objects. This may take some time!');

        /** @var UserGroupRepositoryInterface $repository */
        $repository = app(UserGroupRepositoryInterface::class);

        /** @var UserGroup $userGroup */
        foreach ($repository->getAll() as $userGroup) {
            $this->recalculateForGroup($userGroup);
        }
        $this->friendlyInfo('Recalculated all native amounts.');

        return 0;
    }

    private function recalculateForGroup(UserGroup $userGroup): void
    {
        Log::debug(sprintf('Now recalculating for user group #%d', $userGroup->id));
        $this->recalculateAccounts($userGroup);

        // do a check with the group's currency so we can skip some stuff.
        Preferences::mark();
        $currency = app('amount')->getNativeCurrencyByUserGroup($userGroup);

        $this->recalculatePiggyBanks($userGroup, $currency);
        $this->recalculateBudgets($userGroup, $currency);
        $this->recalculateAvailableBudgets($userGroup, $currency);
        $this->recalculateBills($userGroup, $currency);
        $this->calculateTransactions($userGroup, $currency);

    }

    private function recalculateAccounts(UserGroup $userGroup): void
    {
        $set = $userGroup->accounts()->where(function (EloquentBuilder $q): void {
            $q->whereNotNull('virtual_balance');

            // this needs a different piece of code for postgres.
            if ('pgsql' === config('database.default')) {
                $q->orWhere(DB::raw('CAST(virtual_balance AS TEXT)'), '!=', '');
            }
            if ('pgsql' !== config('database.default')) {
                $q->orWhere('virtual_balance', '!=', '');
            }
        })->get();

        /** @var Account $account */
        foreach ($set as $account) {
            $account->touch();
        }
        Log::debug(sprintf('Recalculated %d accounts for user group #%d.', $set->count(), $userGroup->id));
    }

    private function recalculatePiggyBanks(UserGroup $userGroup, TransactionCurrency $currency): void
    {
        $converter  = new ExchangeRateConverter();
        $converter->setUserGroup($userGroup);
        $converter->setIgnoreSettings(true);
        $repository = app(PiggyBankRepositoryInterface::class);
        $repository->setUserGroup($userGroup);
        $set        = $repository->getPiggyBanks();
        $set        = $set->filter(
            static fn (PiggyBank $piggyBank) => $currency->id !== $piggyBank->transaction_currency_id
        );
        foreach ($set as $piggyBank) {
            $piggyBank->encrypted = false;
            $piggyBank->save();

            foreach ($piggyBank->accounts as $account) {
                $account->pivot->native_current_amount = null;
                if (0 !== bccomp((string) $account->pivot->current_amount, '0')) {
                    $account->pivot->native_current_amount = $converter->convert($piggyBank->transactionCurrency, $currency, today(), (string) $account->pivot->current_amount);
                }
                $account->pivot->save();
            }
            $this->recalculatePiggyBankEvents($piggyBank);
        }
        Log::debug(sprintf('Recalculated %d piggy banks for user group #%d.', $set->count(), $userGroup->id));

    }

    private function recalculatePiggyBankEvents(PiggyBank $piggyBank): void
    {
        $set = $piggyBank->piggyBankEvents()->get();
        $set->each(
            static function (PiggyBankEvent $event): void { // @phpstan-ignore-line
                $event->touch();
            }
        );
        Log::debug(sprintf('Recalculated %d piggy bank events.', $set->count()));
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

    private function calculateTransactions(UserGroup $userGroup, TransactionCurrency $currency): void
    {
        // custom query because of the potential size of this update.
        $set                              = DB::table('transactions')
            ->join('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.user_group_id', $userGroup->id)
            ->where(function (DatabaseBuilder $q1) use ($currency): void {
                $q1->where(function (DatabaseBuilder $q2) use ($currency): void {
                    $q2->whereNot('transactions.transaction_currency_id', $currency->id)->whereNull('transactions.foreign_currency_id');
                })->orWhere(function (DatabaseBuilder $q3) use ($currency): void {
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
        foreach ($set as $item) {
            // here we are.
            /** @var null|Transaction $transaction */
            $transaction = Transaction::find($item->id);
            $transaction?->touch();
        }
        TransactionObserver::$recalculate = true;
        Log::debug(sprintf('Recalculated %d transactions.', $set->count()));
    }
}
