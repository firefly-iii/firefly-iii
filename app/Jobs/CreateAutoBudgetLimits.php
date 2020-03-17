<?php
declare(strict_types=1);
/**
 * CreateAutoBudgetLimits.php
 * Copyright (c) 2020 thegrumpydictator@gmail.com
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
use Log;

/**
 * Class CreateAutoBudgetLimits
 */
class CreateAutoBudgetLimits implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var Carbon The current date */
    private $date;

    /**
     * Create a new job instance.
     *
     * @codeCoverageIgnore
     *
     * @param Carbon $date
     */
    public function __construct(?Carbon $date)
    {
        if (null !== $date) {
            $date->startOfDay();
            $this->date = $date;
        }
        Log::debug(sprintf('Created new CreateAutoBudgetLimits("%s")', $this->date->format('Y-m-d')));
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
     * @param Carbon $date
     */
    public function setDate(Carbon $date): void
    {
        $date->startOfDay();
        $this->date = $date;
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
        $budgetLimit = new BudgetLimit;
        $budgetLimit->budget()->associate($autoBudget->budget);
        $budgetLimit->transactionCurrency()->associate($autoBudget->transactionCurrency);
        $budgetLimit->start_date = $start;
        $budgetLimit->end_date   = $end;
        $budgetLimit->amount     = $amount ?? $autoBudget->amount;
        $budgetLimit->save();

        Log::debug(sprintf('Created budget limit #%d.', $budgetLimit->id));
    }

    /**
     * @param AutoBudget $autoBudget
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
        $currencyId  = (int) $autoBudget->transaction_currency_id;
        $spentAmount = $spent[$currencyId]['sum'] ?? '0';
        Log::debug(sprintf('Spent in previous budget period (%s-%s) is %s', $previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d'), $spentAmount));

        // previous budget limit + this period + spent
        $totalAmount = bcadd(bcadd($budgetLimit->amount, $autoBudget->amount), $spentAmount);
        Log::debug(sprintf('Total amount for current budget period will be %s', $totalAmount));

        if (1 !== bccomp($totalAmount, '0')) {
            Log::info(sprintf('The total amount is negative, so it will be reset to %s.', $totalAmount));
            $totalAmount = $autoBudget->amount;
        }

        // create budget limit:
        $this->createBudgetLimit($autoBudget, $start, $end, $totalAmount);
        Log::debug(sprintf('Done with auto budget #%d', $autoBudget->id));
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
     * @param AutoBudget $autoBudget
     *
     * @throws FireflyException
     */
    private function handleAutoBudget(AutoBudget $autoBudget): void
    {
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

        if (null === $budgetLimit && AutoBudget::AUTO_BUDGET_RESET === $autoBudget->auto_budget_type) {
            // that's easy: create one.
            // do nothing else.
            $this->createBudgetLimit($autoBudget, $start, $end);
            Log::debug(sprintf('Done with auto budget #%d', $autoBudget->id));

            return;
        }

        if (null === $budgetLimit && AutoBudget::AUTO_BUDGET_ROLLOVER === $autoBudget->auto_budget_type) {
            // budget limit exists already,
            $this->createRollover($autoBudget);
            Log::debug(sprintf('Done with auto budget #%d', $autoBudget->id));

            return;
        }
        Log::debug(sprintf('Done with auto budget #%d', $autoBudget->id));
    }

    /**
     * @param AutoBudget $autoBudget
     *
     * @throws FireflyException
     * @return bool
     */
    private function isMagicDay(AutoBudget $autoBudget): bool
    {
        switch ($autoBudget->period) {
            default:
                throw new FireflyException(sprintf('isMagicDay() can\'t handle period "%s"', $autoBudget->period));
            case 'daily':
                // every day is magic!
                return true;
            case 'weekly':
                // fire on Monday.
                return $this->date->isMonday();
            case 'monthly':
                return 1 === $this->date->day;
            case 'quarterly':
                $format = 'm-d';
                $value  = $this->date->format($format);

                return in_array($value, ['01-01', '04-01', '07-01', '10-01'], true);
            case 'half_year':
                $format = 'm-d';
                $value  = $this->date->format($format);

                return in_array($value, ['01-01', '07-01'], true);
                break;
            case 'yearly':
                $format = 'm-d';
                $value  = $this->date->format($format);

                return '01-01' === $value;
        }
    }
}
