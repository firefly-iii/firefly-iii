<?php

/*
 * CorrectAmounts.php
 * Copyright (c) 2023 james@firefly-iii.org
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
use FireflyIII\Models\AutoBudget;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Bill;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\RuleTrigger;
use Illuminate\Console\Command;

/**
 * Class ReportSkeleton
 */
class CorrectAmounts extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'This command makes sure positive and negative amounts are recorded correctly.';
    protected $signature   = 'firefly-iii:fix-amount-pos-neg';

    public function handle(): int
    {
        // auto budgets must be positive
        $this->fixAutoBudgets();
        // available budgets must be positive
        $this->fixAvailableBudgets();
        // bills must be positive (both amounts)
        $this->fixBills();
        // budget limits must be positive
        $this->fixBudgetLimits();
        // currency_exchange_rates must be positive
        $this->fixExchangeRates();
        // piggy_bank_repetitions must be positive
        $this->fixRepetitions();
        // piggy_banks must be positive
        $this->fixPiggyBanks();
        // recurrences_transactions amount must be positive
        $this->fixRecurrences();
        // rule_triggers must be positive or zero (amount_less, amount_more, amount_is)
        $this->fixRuleTriggers();

        return 0;
    }

    private function fixAutoBudgets(): void
    {
        $set   = AutoBudget::where('amount', '<', 0)->get();
        $count = $set->count();
        if (0 === $count) {
            $this->friendlyPositive('All auto budget amounts are positive.');

            return;
        }

        /** @var AutoBudget $item */
        foreach ($set as $item) {
            $item->amount = app('steam')->positive($item->amount);
            $item->save();
        }
        $this->friendlyInfo(sprintf('Corrected %d auto budget amount(s).', $count));
    }

    private function fixAvailableBudgets(): void
    {
        $set   = AvailableBudget::where('amount', '<', 0)->get();
        $count = $set->count();
        if (0 === $count) {
            $this->friendlyPositive('All available budget amounts are positive.');

            return;
        }

        /** @var AvailableBudget $item */
        foreach ($set as $item) {
            $item->amount = app('steam')->positive($item->amount);
            $item->save();
        }
        $this->friendlyInfo(sprintf('Corrected %d available budget amount(s).', $count));
    }

    private function fixBills(): void
    {
        $set   = Bill::where('amount_min', '<', 0)->orWhere('amount_max', '<', 0)->get();
        $count = $set->count();
        if (0 === $count) {
            $this->friendlyPositive('All bill amounts are positive.');

            return;
        }

        /** @var Bill $item */
        foreach ($set as $item) {
            $item->amount_min = app('steam')->positive($item->amount_min);
            $item->amount_max = app('steam')->positive($item->amount_max);
            $item->save();
        }
        $this->friendlyInfo(sprintf('Corrected %d bill amount(s).', $count));
    }

    private function fixBudgetLimits(): void
    {
        $set   = BudgetLimit::where('amount', '<', 0)->get();
        $count = $set->count();
        if (0 === $count) {
            $this->friendlyPositive('All budget limit amounts are positive.');

            return;
        }

        /** @var BudgetLimit $item */
        foreach ($set as $item) {
            $item->amount = app('steam')->positive($item->amount);
            $item->save();
        }
        $this->friendlyInfo(sprintf('Corrected %d budget limit amount(s).', $count));
    }

    private function fixExchangeRates(): void
    {
        $set   = CurrencyExchangeRate::where('rate', '<', 0)->get();
        $count = $set->count();
        if (0 === $count) {
            $this->friendlyPositive('All currency exchange rates are positive.');

            return;
        }

        /** @var CurrencyExchangeRate $item */
        foreach ($set as $item) {
            $item->rate = app('steam')->positive($item->rate);
            $item->save();
        }
        $this->friendlyInfo(sprintf('Corrected %d currency exchange rate(s).', $count));
    }

    private function fixRepetitions(): void
    {
        $set   = PiggyBankRepetition::where('currentamount', '<', 0)->get();
        $count = $set->count();
        if (0 === $count) {
            $this->friendlyPositive('All piggy bank repetition amounts are positive.');

            return;
        }

        /** @var PiggyBankRepetition $item */
        foreach ($set as $item) {
            $item->currentamount = app('steam')->positive($item->current_amount);
            $item->save();
        }
        $this->friendlyInfo(sprintf('Corrected %d piggy bank repetition amount(s).', $count));
    }

    private function fixPiggyBanks(): void
    {
        $set   = PiggyBank::where('targetamount', '<', 0)->get();
        $count = $set->count();
        if (0 === $count) {
            $this->friendlyPositive('All piggy bank amounts are positive.');

            return;
        }

        /** @var PiggyBank $item */
        foreach ($set as $item) {
            $item->targetamount = app('steam')->positive($item->target_amount);
            $item->save();
        }
        $this->friendlyInfo(sprintf('Corrected %d piggy bank amount(s).', $count));
    }

    private function fixRecurrences(): void
    {
        $set   = RecurrenceTransaction::where('amount', '<', 0)
            ->orWhere('foreign_amount', '<', 0)
            ->get()
        ;
        $count = $set->count();
        if (0 === $count) {
            $this->friendlyPositive('All recurring transaction amounts are positive.');

            return;
        }

        /** @var RecurrenceTransaction $item */
        foreach ($set as $item) {
            $item->amount         = app('steam')->positive($item->amount);
            $item->foreign_amount = app('steam')->positive($item->foreign_amount);
            $item->save();
        }
        $this->friendlyInfo(sprintf('Corrected %d recurring transaction amount(s).', $count));
    }

    private function fixRuleTriggers(): void
    {
        $set   = RuleTrigger::whereIn('trigger_type', ['amount_less', 'amount_more', 'amount_is'])->get();
        $fixed = 0;

        /** @var RuleTrigger $item */
        foreach ($set as $item) {
            // basic check:
            $check = 0;

            try {
                $check = bccomp((string)$item->trigger_value, '0');
            } catch (\ValueError $e) {
                $this->friendlyError(sprintf('Rule #%d contained invalid %s-trigger "%s". The trigger has been removed, and the rule is disabled.', $item->rule_id, $item->trigger_type, $item->trigger_value));
                $item->rule->active = false;
                $item->rule->save();
                $item->forceDelete();
            }
            if (-1 === $check) {
                ++$fixed;
                $item->trigger_value = app('steam')->positive($item->trigger_value);
                $item->save();
            }
        }
        if (0 === $fixed) {
            $this->friendlyPositive('All rule trigger amounts are positive.');

            return;
        }
        $this->friendlyInfo(sprintf('Corrected %d rule trigger amount(s).', $fixed));
    }
}
