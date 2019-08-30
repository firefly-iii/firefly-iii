<?php
/**
 * OperationsRepository.php
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

namespace FireflyIII\Repositories\Budget;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 *
 * Class OperationsRepository
 */
class OperationsRepository implements OperationsRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
            die(get_class($this));
        }
    }

    /**
     * A method that returns the amount of money budgeted per day for this budget,
     * on average.
     *
     * @param Budget $budget
     *
     * @return string
     * @deprecated
     */
    public function budgetedPerDay(Budget $budget): string
    {
        Log::debug(sprintf('Now with budget #%d "%s"', $budget->id, $budget->name));
        $total = '0';
        $count = 0;
        foreach ($budget->budgetlimits as $limit) {
            $diff   = $limit->start_date->diffInDays($limit->end_date);
            $diff   = 0 === $diff ? 1 : $diff;
            $amount = (string)$limit->amount;
            $perDay = bcdiv($amount, (string)$diff);
            $total  = bcadd($total, $perDay);
            $count++;
            Log::debug(sprintf('Found %d budget limits. Per day is %s, total is %s', $count, $perDay, $total));
        }
        $avg = $total;
        if ($count > 0) {
            $avg = bcdiv($total, (string)$count);
        }
        Log::debug(sprintf('%s / %d = %s = average.', $total, $count, $avg));

        return $avg;
    }

    /**
     * This method collects various info on budgets, used on the budget page and on the index.
     *
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @deprecated
     *
     */
    public function collectBudgetInformation(Collection $budgets, Carbon $start, Carbon $end): array
    {
        // get account information
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accounts          = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $defaultCurrency   = app('amount')->getDefaultCurrency();
        $return            = [];
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $budgetId          = $budget->id;
            $return[$budgetId] = [
                'spent'    => $this->spentInPeriod(new Collection([$budget]), $accounts, $start, $end),
                'budgeted' => '0',
            ];
            $budgetLimits      = $this->getBudgetLimits($budget, $start, $end);
            $otherLimits       = new Collection;

            // get all the budget limits relevant between start and end and examine them:
            /** @var BudgetLimit $limit */
            foreach ($budgetLimits as $limit) {
                if ($limit->start_date->isSameDay($start) && $limit->end_date->isSameDay($end)
                ) {
                    $return[$budgetId]['currentLimit'] = $limit;
                    $return[$budgetId]['budgeted']     = round($limit->amount, $defaultCurrency->decimal_places);
                    continue;
                }
                // otherwise it's just one of the many relevant repetitions:
                $otherLimits->push($limit);
            }
            $return[$budgetId]['otherLimits'] = $otherLimits;
        }

        return $return;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     * @deprecated
     */
    public function spentInPeriod(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): string
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setBudgets($budgets)->withBudgetInformation();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }

        return $collector->getSum();
    }

    /**
     * For now, simply refer to whichever repository holds this function.
     * TODO might be done better in the future.
     *
     * @param Budget      $budget
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return Collection
     */
    private function getBudgetLimits(Budget $budget, Carbon $start = null, Carbon $end = null): Collection
    {
        /** @var BudgetLimitRepositoryInterface $blRepository */
        $blRepository = app(BudgetLimitRepositoryInterface::class);

        return $blRepository->getBudgetLimits($budget, $start, $end);
    }
}