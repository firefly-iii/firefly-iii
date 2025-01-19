<?php

/**
 * BudgetLimitCurrency.php
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

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\User;
use Illuminate\Console\Command;

class UpgradesBudgetLimits extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '480_bl_currency';

    protected $description          = 'Give budget limits a currency';

    protected $signature            = 'upgrade:480-budget-limit-currencies {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @throws FireflyException
     */
    public function handle(): int
    {
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->friendlyInfo('This command has already been executed.');

            return 0;
        }

        $count        = 0;
        $budgetLimits = BudgetLimit::get();

        /** @var BudgetLimit $budgetLimit */
        foreach ($budgetLimits as $budgetLimit) {
            if (null === $budgetLimit->transaction_currency_id) {
                /** @var null|Budget $budget */
                $budget = $budgetLimit->budget;
                if (null !== $budget) {
                    /** @var null|User $user */
                    $user = $budget->user;
                    if (null !== $user) {
                        $currency                             = app('amount')->getNativeCurrencyByUserGroup($user->userGroup);
                        $budgetLimit->transaction_currency_id = $currency->id;
                        $budgetLimit->save();
                        $this->friendlyInfo(
                            sprintf('Budget limit #%d (part of budget "%s") now has a currency setting (%s).', $budgetLimit->id, $budget->name, $currency->name)
                        );
                        ++$count;
                    }
                }
            }
        }
        $this->markAsExecuted();

        return 0;
    }

    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool) $configVar->data;
        }

        return false;
    }

    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}
