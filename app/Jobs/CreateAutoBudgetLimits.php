<?php

/**
 * CreateAutoBudgetLimits.php
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

namespace FireflyIII\Jobs;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\AutoBudget;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class CreateAutoBudgetLimits
 */
class CreateAutoBudgetLimits implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private Carbon $date;

    /**
     * Create a new job instance.
     *
     *
     * @param Carbon|null $date
     */
    public function __construct(?Carbon $date)
    {
        if (null !== $date) {
            $newDate = clone $date;
            $newDate->startOfDay();
            $this->date = $newDate;
            Log::debug(sprintf('Created new CreateAutoBudgetLimits("%s")', $this->date->format('Y-m-d')));
        }
    }

    /**
     * Execute the job.
     *
     * @throws FireflyException
     */
    public function handle(): void
    {
        Log::debug(sprintf('Now at start of CreateAutoBudgetLimits() job for %s.', $this->date->format('D d M Y')));
        $autoBudgets = AutoBudget::get();
        Log::debug(sprintf('Found %d auto budgets.', $autoBudgets->count()));
        foreach ($autoBudgets as $autoBudget) {
            $this->handleAutoBudget($autoBudget);
        }
    }

    /**
     * @param AutoBudget $autoBudget
     *
     * @throws FireflyException
     */
    private function handleAutoBudget(AutoBudget $autoBudget): void
    {
        if (null === $autoBudget->budget) {
            Log::info(sprintf('Auto budget #%d is associated with a deleted budget.', $autoBudget->id));
            $autoBudget->delete();

            return;
        }
        if (false === $autoBudget->budget->active) {
            Log::info(sprintf('Auto budget #%d is associated with an inactive budget.', $autoBudget->id));

            return;
        }
        if (!$this->isMagicDay($autoBudget)) {
            Log::info(
                sprintf(
                    'Today (%s) is not a magic day for %s auto-budget #%d (part of budget #%d "%s")',
                    $this->date->format('Y-m-d'),
                    $autoBudget->period,
                    $autoBudget->id,
                    $autoBudget->budget->id,
                    $autoBudget->budget->name
                )
            );
            Log::debug(sprintf('Done with auto budget #%d', $autoBudget->id));

            return;
        }
        Log::info(
            sprintf(
                'Today (%s) is a magic day for %s auto-budget #%d (part of budget #%d "%s")',
                $this->date->format('Y-m-d'),
                $autoBudget->period,
                $autoBudget->id,
                $autoBudget->budget->id,
                $autoBudget->budget->name
            )
        );

        // get date range for budget limit, based on range in auto-budget
        $start = app('navigation')->startOfPeriod($this->date, $autoBudget->period);
        $end   = app('navigation')->endOfPeriod($start, $autoBudget->period);

        // find budget limit:
        $budgetLimit = $this->findBudgetLimit($autoBudget->budget, $start, $end);

        if (null === $budgetLimit && AutoBudget::AUTO_BUDGET_RESET === (int)$autoBudget->auto_budget_type) {
            // that's easy: create one.
            // do nothing else.
            $this->createBudgetLimit($autoBudget, $start, $end);
            Log::debug(sprintf('Done with auto budget #%d', $autoBudget->id));

            return;
        }

        if (null === $budgetLimit && AutoBudget::AUTO_BUDGET_ROLLOVER === (int)$autoBudget->auto_budget_type) {
            // budget limit exists already,
            $this->createRollover($autoBudget);
            Log::debug(sprintf('Done with auto budget #%d', $autoBudget->id));

            return;
        }
        if (null === $budgetLimit && AutoBudget::AUTO_BUDGET_ADJUSTED === (int)$autoBudget->auto_budget_type) {
            // budget limit exists already,
            $this->createAdjustedLimit($autoBudget);
            Log::debug(sprintf('Done with auto budget #%d', $autoBudget->id));

            return;
        }
        Log::debug(sprintf('Done with auto budget #%d', $autoBudget->id));
    }

    /**
     * @param AutoBudget $autoBudget
     *
     * @return bool
     * @throws FireflyException
     */
    private function isMagicDay(AutoBudget $autoBudget): bool
    {
        if ('daily' === $autoBudget->period) {
            return true;
        }

        if ('weekly' === $autoBudget->period) {
            return $this->date->isMonday();
        }

        if ('monthly' === $autoBudget->period) {
            return 1 === $this->date->day;
        }
        if ('quarterly' === $autoBudget->period) {
            $format = 'm-d';
            $value  = $this->date->format($format);

            return in_array($value, ['01-01', '04-01', '07-01', '10-01'], true);
        }
        if ('half_year' === $autoBudget->period) {
            $format = 'm-d';
            $value  = $this->date->format($format);

            return in_array($value, ['01-01', '07-01'], true);
        }
        if ('yearly' === $autoBudget->period) {
            $format = 'm-d';
            $value  = $this->date->format($format);

            return '01-01' === $value;
        }
        throw new FireflyException(sprintf('isMagicDay() can\'t handle period "%s"', $autoBudget->period));
    }

    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return BudgetLimit|null
     */
    private function findBudgetLimit(Budget $budget, Carbon $start, Carbon $end): ?BudgetLimit
    {
        Log::debug(
            sprintf(
                'Going to find a budget limit for budget #%d ("%s") between %s and %s',
                $budget->id,
                $budget->name,
                $start->format('Y-m-d'),
                $end->format('Y-m-d')
            )
        );

        return $budget->budgetlimits()
                      ->where('start_date', $start->format('Y-m-d'))
                      ->where('end_date', $end->format('Y-m-d'))->first();
    }

    /**
     * @param AutoBudget  $autoBudget
     * @param Carbon      $start
     * @param Carbon      $end
     * @param string|null $amount
     */
    private function createBudgetLimit(AutoBudget $autoBudget, Carbon $start, Carbon $end, ?string $amount = null)
    {
        Log::debug(sprintf('No budget limit exist. Must create one for auto-budget #%d', $autoBudget->id));
        if (null !== $amount) {
            Log::debug(sprintf('Amount is overruled and will be set to %s', $amount));
        }
        $budgetLimit = new BudgetLimit();
        $budgetLimit->budget()->associate($autoBudget->budget);
        $budgetLimit->transactionCurrency()->associate($autoBudget->transactionCurrency);
        $budgetLimit->start_date = $start;
        $budgetLimit->end_date   = $end;
        $budgetLimit->amount     = $amount ?? $autoBudget->amount;
        $budgetLimit->period     = $autoBudget->period;
        $budgetLimit->generated  = true;
        $budgetLimit->save();

        Log::debug(sprintf('Created budget limit #%d.', $budgetLimit->id));
    }

    /**
     * @param AutoBudget $autoBudget
     *
     * @throws FireflyException
     */
    private function createRollover(AutoBudget $autoBudget): void
    {
        Log::debug(sprintf('Will now manage rollover for auto budget #%d', $autoBudget->id));
        // current period:
        $start = app('navigation')->startOfPeriod($this->date, $autoBudget->period);
        $end   = app('navigation')->endOfPeriod($start, $autoBudget->period);

        // which means previous period:
        $previousStart = app('navigation')->subtractPeriod($start, $autoBudget->period);
        $previousEnd   = app('navigation')->endOfPeriod($previousStart, $autoBudget->period);

        Log::debug(
            sprintf(
                'Current period is %s-%s, so previous period is %s-%s',
                $start->format('Y-m-d'),
                $end->format('Y-m-d'),
                $previousStart->format('Y-m-d'),
                $previousEnd->format('Y-m-d')
            )
        );

        // has budget limit in previous period?
        $budgetLimit = $this->findBudgetLimit($autoBudget->budget, $previousStart, $previousEnd);

        if (null === $budgetLimit) {
            Log::debug('No budget limit exists in previous period, so create one.');
            // if not, create it and we're done.
            $this->createBudgetLimit($autoBudget, $start, $end);
            Log::debug(sprintf('Done with auto budget #%d', $autoBudget->id));

            return;
        }
        Log::debug('Budget limit exists for previous period.');
        // if has one, calculate expenses and use that as a base.
        $repository = app(OperationsRepositoryInterface::class);
        $repository->setUser($autoBudget->budget->user);
        $spent       = $repository->sumExpenses($previousStart, $previousEnd, null, new Collection([$autoBudget->budget]), $autoBudget->transactionCurrency);
        $currencyId  = (int)$autoBudget->transaction_currency_id;
        $spentAmount = $spent[$currencyId]['sum'] ?? '0';
        Log::debug(sprintf('Spent in previous budget period (%s-%s) is %s', $previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d'), $spentAmount));

        // if you spent more in previous budget period, than whatever you had previous budget period, the amount resets
        // previous budget limit + spent
        $budgetLeft  = bcadd($budgetLimit->amount, $spentAmount);
        $totalAmount = $autoBudget->amount;
        Log::debug(sprintf('Total amount left for previous budget period is %s', $budgetLeft));

        if (-1 !== bccomp('0', $budgetLeft)) {
            Log::info(sprintf('The amount left is negative, so it will be reset to %s.', $totalAmount));
        }
        if (1 !== bccomp('0', $budgetLeft)) {
            $totalAmount = bcadd($budgetLeft, $totalAmount);
            Log::info(sprintf('The amount left is positive, so the new amount will be %s.', $totalAmount));
        }

        // create budget limit:
        $this->createBudgetLimit($autoBudget, $start, $end, $totalAmount);
        Log::debug(sprintf('Done with auto budget #%d', $autoBudget->id));
    }

    /**
     * @param AutoBudget $autoBudget
     *
     * @return void
     */
    private function createAdjustedLimit(AutoBudget $autoBudget): void
    {
        Log::debug(sprintf('Will now manage rollover for auto budget #%d', $autoBudget->id));
        // current period:
        $start = app('navigation')->startOfPeriod($this->date, $autoBudget->period);
        $end   = app('navigation')->endOfPeriod($start, $autoBudget->period);

        // which means previous period:
        $previousStart = app('navigation')->subtractPeriod($start, $autoBudget->period);
        $previousEnd   = app('navigation')->endOfPeriod($previousStart, $autoBudget->period);

        Log::debug(
            sprintf(
                'Current period is %s-%s, so previous period is %s-%s',
                $start->format('Y-m-d'),
                $end->format('Y-m-d'),
                $previousStart->format('Y-m-d'),
                $previousEnd->format('Y-m-d')
            )
        );

        // has budget limit in previous period?
        $budgetLimit = $this->findBudgetLimit($autoBudget->budget, $previousStart, $previousEnd);

        if (null === $budgetLimit) {
            Log::debug('No budget limit exists in previous period, so create one.');
            // if not, create standard amount, and we're done.
            $this->createBudgetLimit($autoBudget, $start, $end);
            return;
        }
        Log::debug('Budget limit exists for previous period.');

        // if has one, calculate expenses and use that as a base.
        $repository = app(OperationsRepositoryInterface::class);
        $repository->setUser($autoBudget->budget->user);
        $spent       = $repository->sumExpenses($previousStart, $previousEnd, null, new Collection([$autoBudget->budget]), $autoBudget->transactionCurrency);
        $currencyId  = (int)$autoBudget->transaction_currency_id;
        $spentAmount = $spent[$currencyId]['sum'] ?? '0';
        Log::debug(sprintf('Spent in previous budget period (%s-%s) is %s', $previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d'), $spentAmount));

        // what you spent in previous period PLUS the amount for the current period,
        // if that is more than zero, that's the amount that will be set.

        $budgetAvailable = bcadd(bcadd($budgetLimit->amount, $autoBudget->amount), $spentAmount);
        $totalAmount     = $autoBudget->amount;
        Log::debug(sprintf('Total amount available for current budget period is %s', $budgetAvailable));


        if (-1 !== bccomp($budgetAvailable, $totalAmount)) {
            Log::info(sprintf('There is no overspending, no need to adjust. Budget limit amount will be %s.', $budgetAvailable));
            // create budget limit:
            $this->createBudgetLimit($autoBudget, $start, $end, $budgetAvailable);
        }
        if (1 !== bccomp($budgetAvailable, $totalAmount) && 1 === bccomp($budgetAvailable, '0')) {
            Log::info(sprintf('There was overspending, so the new amount will be %s.', $budgetAvailable));
            // create budget limit:
            $this->createBudgetLimit($autoBudget, $start, $end, $budgetAvailable);
        }
        if (1 !== bccomp($budgetAvailable, $totalAmount) && -1 === bccomp($budgetAvailable, '0')) {
            Log::info('There was overspending, but so much even this period cant fix that. Reset it to 1.');
            // create budget limit:
            $this->createBudgetLimit($autoBudget, $start, $end, '1');
        }
        Log::debug(sprintf('Done with auto budget #%d', $autoBudget->id));
    }

    /**
     * @param Carbon $date
     */
    public function setDate(Carbon $date): void
    {
        $newDate = clone $date;
        $newDate->startOfDay();
        $this->date = $newDate;
    }
}
