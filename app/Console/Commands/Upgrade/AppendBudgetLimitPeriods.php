<?php
/*
 * AppendBudgetLimitPeriods.php
 * Copyright (c) 2021 james@firefly-iii.org
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

use FireflyIII\Models\BudgetLimit;
use Illuminate\Console\Command;
use Log;

class AppendBudgetLimitPeriods extends Command
{
    public const CONFIG_NAME = '550_budget_limit_periods';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Append budget limits with their (estimated) timeframe.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:budget-limit-periods {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $start = microtime(true);
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }

        $this->theresNoLimit();

        $this->markAsExecuted();

        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Fixed budget limits in %s seconds.', $end));

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
    private function theresNoLimit(): void
    {
        $limits = BudgetLimit::whereNull('period')->get();
        /** @var BudgetLimit $limit */
        foreach ($limits as $limit) {
            $this->fixLimit($limit);
        }
    }

    /**
     * @param BudgetLimit $limit
     */
    private function fixLimit(BudgetLimit $limit)
    {
        $period = $this->getLimitPeriod($limit);

        if (null === $period) {
            $message = sprintf(
                'Could not guesstimate budget limit #%d (%s - %s) period.', $limit->id, $limit->start_date->format('Y-m-d'), $limit->end_date->format('Y-m-d')
            );
            $this->warn($message);
            Log::warning($message);

            return;
        }
        $limit->period = $period;
        $limit->save();

        $msg = sprintf(
            'Budget limit #%d (%s - %s) period is "%s".', $limit->id, $limit->start_date->format('Y-m-d'), $limit->end_date->format('Y-m-d'), $period
        );
        Log::debug($msg);

    }

    /**
     * @param BudgetLimit $limit
     *
     * @return string|null
     */
    private function getLimitPeriod(BudgetLimit $limit): ?string
    {
        // is daily
        if ($limit->end_date->isSameDay($limit->start_date)) {
            return 'daily';
        }
        // is weekly
        if ('1' === $limit->start_date->format('N') && '7' === $limit->end_date->format('N') && 6 === $limit->end_date->diffInDays($limit->start_date)) {
            return 'weekly';
        }

        // is monthly
        if (
            '1' === $limit->start_date->format('j') // first day
            && $limit->end_date->format('j') === $limit->end_date->format('t') // last day
            && $limit->start_date->isSameMonth($limit->end_date)
        ) {
            return 'monthly';
        }

        // is quarter
        $start = ['1-1', '1-4', '1-7', '1-10'];
        $end   = ['31-3', '30-6', '30-9', '31-12'];
        if (
            in_array($limit->start_date->format('j-n'), $start, true) // start of quarter
            && in_array($limit->end_date->format('j-n'), $end, true) // end of quarter
            && 2 === $limit->start_date->diffInMonths($limit->end_date)
        ) {
            return 'quarterly';
        }
        // is half year
        $start = ['1-1', '1-7'];
        $end   = ['30-6', '31-12'];
        if (
            in_array($limit->start_date->format('j-n'), $start) // start of quarter
            && in_array($limit->end_date->format('j-n'), $end) // end of quarter
            && 5 === $limit->start_date->diffInMonths($limit->end_date)
        ) {
            return 'half_year';
        }
        // is yearly
        if ('1-1' === $limit->start_date->format('j-n') && '31-12' === $limit->end_date->format('j-n')) {
            return 'yearly';
        }

        return null;
    }

    /**
     *
     */
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}
