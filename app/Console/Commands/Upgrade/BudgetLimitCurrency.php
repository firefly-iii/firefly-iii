<?php
/**
 * BudgetLimitCurrency.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Upgrade;


use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use Illuminate\Console\Command;

/**
 * Class BudgetLimitCurrency
 */
class BudgetLimitCurrency extends Command
{
    public const CONFIG_NAME = '4780_bl_currency';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Give budget limits a currency';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:bl-currency {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $start = microtime(true);
        // @codeCoverageIgnoreStart
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }
        // @codeCoverageIgnoreEnd

        $count        = 0;
        $budgetLimits = BudgetLimit::get();
        /** @var BudgetLimit $budgetLimit */
        foreach ($budgetLimits as $budgetLimit) {
            if (null === $budgetLimit->transaction_currency_id) {
                /** @var Budget $budget */
                $budget = $budgetLimit->budget;
                if (null !== $budget) {
                    $user = $budget->user;
                    if (null !== $user) {
                        $currency                             = app('amount')->getDefaultCurrencyByUser($user);
                        $budgetLimit->transaction_currency_id = $currency->id;
                        $budgetLimit->save();
                        $this->line(
                            sprintf('Budget limit #%d (part of budget "%s") now has a currency setting (%s).', $budgetLimit->id, $budget->name, $currency->name)
                        );
                        $count++;
                    }
                }
            }
        }
        if (0 === $count) {
            $this->info('All budget limits are correct.');
        }
        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Verified budget limits in %s seconds.', $end));

        $this->markAsExecuted();

        return 0;
    }

    /**
     * @return bool
     */
    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool)$configVar->data;
        }

        return false; // @codeCoverageIgnore
    }


    /**
     *
     */
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}
