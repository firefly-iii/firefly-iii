<?php

/**
 * EnableCurrencies.php
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CorrectsCurrencies extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Enables all currencies in use.';
    protected $signature   = 'correction:currencies';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userGroups = UserGroup::get();
        foreach ($userGroups as $userGroup) {
            $this->correctCurrencies($userGroup);
        }

        return CommandAlias::SUCCESS;
    }

    private function correctCurrencies(UserGroup $userGroup): void
    {
        /** @var CurrencyRepositoryInterface $repos */
        $repos           = app(CurrencyRepositoryInterface::class);

        // first check if the user has any default currency (not necessarily the case, so can be forced).
        $defaultCurrency = app('amount')->getNativeCurrencyByUserGroup($userGroup);

        Log::debug(sprintf('Now correcting currencies for user group #%d', $userGroup->id));
        $found           = [$defaultCurrency->id];

        // get all meta entries
        $meta            = AccountMeta::leftJoin('accounts', 'accounts.id', '=', 'account_meta.account_id')
            ->where('accounts.user_group_id', $userGroup->id)
            ->where('account_meta.name', 'currency_id')->groupBy('data')->get(['data'])
        ;
        foreach ($meta as $entry) {
            $found[] = (int) $entry->data;
        }

        // get all from journals:
        $journals        = TransactionJournal::where('user_group_id', $userGroup->id)
            ->groupBy('transaction_currency_id')->get(['transaction_currency_id'])
        ;
        foreach ($journals as $entry) {
            $found[] = (int) $entry->transaction_currency_id;
        }

        // get all from transactions
        $transactions    = Transaction::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.user_group_id', $userGroup->id)
            ->groupBy('transactions.transaction_currency_id', 'transactions.foreign_currency_id')
            ->get(['transactions.transaction_currency_id', 'transactions.foreign_currency_id'])
        ;
        foreach ($transactions as $entry) {
            $found[] = (int) $entry->transaction_currency_id;
            $found[] = (int) $entry->foreign_currency_id;
        }

        // get all from budget limits
        $limits          = BudgetLimit::leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
            ->groupBy('transaction_currency_id')
            ->get(['budget_limits.transaction_currency_id'])
        ;
        foreach ($limits as $entry) {
            $found[] = $entry->transaction_currency_id;
        }

        // also get all currencies already enabled.
        $alreadyEnabled  = $userGroup->currencies()->get(['transaction_currencies.id'])->pluck('id')->toArray();
        foreach ($alreadyEnabled as $currencyId) {
            $found[] = $currencyId;
        }

        $found           = array_values(array_unique($found));
        $found           = array_values(
            array_filter(
                $found,
                static function (int $currencyId) {
                    return 0 !== $currencyId;
                }
            )
        );

        $valid           = new Collection();

        /** @var int $currencyId */
        foreach ($found as $currencyId) {
            $currency = $repos->find($currencyId);
            if (null !== $currency) {
                $valid->push($currency);
            }
        }
        $ids             = $valid->pluck('id')->toArray();
        Log::debug(sprintf('Found currencies for user group #%d: %s', $userGroup->id, implode(', ', $ids)));
        $userGroup->currencies()->sync($ids);

        /** @var GroupMembership $membership */
        foreach ($userGroup->groupMemberships()->get() as $membership) {
            // make sure no individual different preferences.
            $membership->user->currencies()->sync([]);
        }
    }
}
