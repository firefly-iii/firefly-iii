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
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\RuleTrigger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CorrectsAmounts extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'This command makes sure positive and negative amounts are recorded correctly.';
    protected $signature   = 'correction:amounts';

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
        $count = AutoBudget::where('amount', '<', 0)->update(['amount' => DB::raw('amount * -1')]);
        if (0 === $count) {

            return;
        }
        $this->friendlyInfo(sprintf('Corrected %d auto budget amount(s).', $count));
    }

    private function fixAvailableBudgets(): void
    {
        $count = AvailableBudget::where('amount', '<', 0)->update(['amount' => DB::raw('amount * -1')]);
        if (0 === $count) {

            return;
        }
        $this->friendlyInfo(sprintf('Corrected %d available budget amount(s).', $count));
    }

    private function fixBills(): void
    {
        $count = 0;
        $count += Bill::where('amount_max', '<', 0)->update(['amount_max' => DB::raw('amount_max * -1')]);
        $count += Bill::where('amount_min', '<', 0)->update(['amount_min' => DB::raw('amount_min * -1')]);
        if (0 === $count) {

            return;
        }
        $this->friendlyInfo(sprintf('Corrected %d bill amount(s).', $count));
    }

    private function fixBudgetLimits(): void
    {
        $count = BudgetLimit::where('amount', '<', 0)->update(['amount' => DB::raw('amount * -1')]);
        if (0 === $count) {

            return;
        }
        $this->friendlyInfo(sprintf('Corrected %d budget limit amount(s).', $count));
    }

    private function fixExchangeRates(): void
    {
        $count = CurrencyExchangeRate::where('rate', '<', 0)->update(['rate' => DB::raw('rate * -1')]);
        if (0 === $count) {

            return;
        }
        $this->friendlyInfo(sprintf('Corrected %d currency exchange rate(s).', $count));
    }

    private function fixPiggyBanks(): void
    {
        $count = PiggyBank::where('target_amount', '<', 0)->update(['target_amount' => DB::raw('target_amount * -1')]);
        if (0 === $count) {

            return;
        }
        $this->friendlyInfo(sprintf('Corrected %d piggy bank amount(s).', $count));
    }

    private function fixRecurrences(): void
    {
        $count = 0;
        $count += RecurrenceTransaction::where('amount', '<', 0)->update(['amount' => DB::raw('amount * -1')]);
        $count += RecurrenceTransaction::where('foreign_amount', '<', 0)->update(['foreign_amount' => DB::raw('foreign_amount * -1')]);
        if (0 === $count) {

            return;
        }
        $this->friendlyInfo(sprintf('Corrected %d recurring transaction amount(s).', $count));
    }

    /**
     * Foreach loop is unavoidable here.
     */
    private function fixRuleTriggers(): void
    {
        $set   = RuleTrigger::whereIn('trigger_type', ['amount_less', 'amount_more', 'amount_is'])->get();
        $fixed = 0;

        /** @var RuleTrigger $item */
        foreach ($set as $item) {
            $result = $this->fixRuleTrigger($item);
            if (true === $result) {
                ++$fixed;
            }
        }
        if (0 === $fixed) {

            return;
        }
        $this->friendlyInfo(sprintf('Corrected %d rule trigger amount(s).', $fixed));
    }

    private function fixRuleTrigger(RuleTrigger $item): bool
    {
        try {
            $check = bccomp((string) $item->trigger_value, '0');
        } catch (\ValueError $e) {
            $this->friendlyError(sprintf('Rule #%d contained invalid %s-trigger "%s". The trigger has been removed, and the rule is disabled.', $item->rule_id, $item->trigger_type, $item->trigger_value));
            $item->rule->active = false;
            $item->rule->save();
            $item->forceDelete();

            return false;
        }
        if (-1 === $check) {
            $item->trigger_value = app('steam')->positive($item->trigger_value);
            $item->save();

            return true;
        }

        return false;
    }
}
