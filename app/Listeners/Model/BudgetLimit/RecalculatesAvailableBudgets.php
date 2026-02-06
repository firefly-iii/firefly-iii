<?php
/*
 * RecalculatesAvailableBudgets.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Listeners\Model\BudgetLimit;

use Carbon\Carbon;
use FireflyIII\Events\Model\BudgetLimit\CreatedBudgetLimit;
use FireflyIII\Events\Model\BudgetLimit\DestroyedBudgetLimit;
use FireflyIII\Events\Model\BudgetLimit\UpdatedBudgetLimit;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Navigation;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\Precision;

class RecalculatesAvailableBudgets implements ShouldQueue
{
    private AvailableBudgetRepositoryInterface                             $repository;
    private BudgetLimitRepositoryInterface                                 $blRepository;
    private TransactionCurrency                                            $currency;
    private DestroyedBudgetLimit | CreatedBudgetLimit | UpdatedBudgetLimit $event;

    public function handle(DestroyedBudgetLimit | CreatedBudgetLimit | UpdatedBudgetLimit $event): void
    {
        $this->event = $event;
        $start       = null;
        $end         = null;
        $user        = null;
        if ($event instanceof DestroyedBudgetLimit) {
            $start          = $event->start->clone();
            $end            = $event->end->clone();
            $user           = $event->user;
            $this->currency = Amount::getPrimaryCurrencyByUserGroup($user->userGroup);
        }
        if (!($event instanceof DestroyedBudgetLimit)) {
            $start          = $event->budgetLimit->start_date->clone();
            $end            = $event->budgetLimit->end_date->clone();
            $user           = $event->budgetLimit->budget->user;
            $this->currency = $event->budgetLimit->transactionCurrency;
        }
        if (null === $start || null === $end || null == $user) {
            Log::error(sprintf('Cannot deal with event %s, because start and end are NULL.', get_class($event)));
            return;
        }
        Log::debug(sprintf('Noticed event %s.', get_class($event)));
        $this->refreshAvailableBudgets($user, $start, $end);
    }

    private function calculateAmount(AvailableBudget $availableBudget): void
    {
        Log::debug(sprintf('Now in calculateAmount(#%d)', $availableBudget->id));
        $newAmount = '0';
        $period    = Period::make($availableBudget->start_date, $availableBudget->end_date, Precision::DAY());
        Log::debug(sprintf('Now recalculating available budget #%d, (%s to %s)', $availableBudget->id, $availableBudget->start_date->format('Y-m-d'), $availableBudget->end_date->format('Y-m-d')));
        // have to recalculate everything just in case.
        $set = $this->blRepository->getAllBudgetLimitsByCurrency($availableBudget->transactionCurrency, $availableBudget->start_date, $availableBudget->end_date);
        Log::debug(sprintf('Found %d interesting budget limit(s).', $set->count()));

        /** @var BudgetLimit $budgetLimit */
        foreach ($set as $budgetLimit) {
            $newAmount = bcadd($newAmount, $this->getAmountFromBudgetLimit($budgetLimit, $period));
        }
        if (0 === bccomp('0', $newAmount)) {
            Log::debug('New amount is zero, deleting AB.');
            $availableBudget->delete();

            return;
        }
        Log::debug(sprintf('Concluded new amount for this AB must be %s', $newAmount));
        $this->repository->update($availableBudget, ['amount' => $newAmount]);
    }

    private function correctViewRange(string $viewRange): string
    {
        if ('MTD' === $viewRange) {
            Log::debug(sprintf('Overrule %s to 1M', $viewRange));
            return '1M';
        }
        if ('QTD' === $viewRange) {
            Log::debug(sprintf('Overrule %s to 3M', $viewRange));
            return '3M';
        }
        if ('YTD' === $viewRange) {
            Log::debug(sprintf('Overrule %s to 1Y', $viewRange));
            return '1Y';
        }
        return $viewRange;
    }

    private function getAmountFromBudgetLimit(BudgetLimit $budgetLimit, Period $availableBudgetPeriod): string
    {
        Log::debug(sprintf('Found interesting budget limit #%d (%s to %s)', $budgetLimit->id, $budgetLimit->start_date->format('Y-m-d'), $budgetLimit->end_date->format('Y-m-d')));
        // overlap in days:
        $limitPeriod = Period::make($budgetLimit->start_date, $budgetLimit->end_date, precision: Precision::DAY(), boundaries: Boundaries::EXCLUDE_NONE());
        // if both equal each other, amount from this BL must be added to the AB
        if ($limitPeriod->equals($availableBudgetPeriod)) {
            Log::debug('This budget limit is equal to the available budget period.');
            return (string)$budgetLimit->amount;
        }
        // if budget limit period is inside AB period, it can be added in full.
        if (!$limitPeriod->equals($availableBudgetPeriod) && $availableBudgetPeriod->contains($limitPeriod)) {
            Log::debug('This budget limit is smaller than the available budget period.');
            return (string)$budgetLimit->amount;
        }

        if (!$limitPeriod->equals($availableBudgetPeriod) && !$availableBudgetPeriod->contains($limitPeriod) && $availableBudgetPeriod->overlapsWith($limitPeriod)) {
            Log::debug('This budget limit is something else entirely!');
            $overlap = $availableBudgetPeriod->overlap($limitPeriod);
            if ($overlap instanceof Period) {
                $length = $overlap->length();
                return bcmul($this->blRepository->getDailyAmount($budgetLimit), (string)$length);
            }
        }
        return '0';
    }

    private function refreshAvailableBudget(Carbon $start, string $viewRange): void
    {
        $end = Navigation::endOfPeriod($start, $viewRange);
        Log::debug(sprintf('refreshAvailableBudget(%s, %s), end is %s', $start->format('Y-m-d'), $viewRange, $end->format('Y-m-d')));
        $availableBudget = $this->repository->find($this->currency, $start, $end);

        if (null !== $availableBudget) {
            Log::debug('Found available budget for this period, will update it.');
            $this->calculateAmount($availableBudget);
            return;
        }
        if ($this->event instanceof DestroyedBudgetLimit) {
            Log::debug('Budget limit is deleted, but there was no available budget. Can stop here.');
            return;
        }
        if ($end->lt($start)) {
            Log::error(sprintf('%s is less than %s, stop.', $start->format('Y-m-d'), $end->format('Y-m-d')));
            return;
        }
        Log::debug(sprintf('Will create new available budget for period %s to %s', $start->format('Y-m-d'), $end->format('Y-m-d')));
        $availableBudget = $this->repository->store(
            [
                'start'       => $start,
                'end'         => $end,
                'currency_id' => $this->currency->id,
                'amount'      => '1',
            ]
        );
        Log::debug(sprintf('ID of new available budget is #%d', $availableBudget->id));
        $this->calculateAmount($availableBudget);
    }

    private function refreshAvailableBudgets(User $user, Carbon $start, Carbon $end): void
    {
        Log::debug(sprintf('Now in refreshAvailableBudget(%s, %s)', $start->format('Y-m-d'), $end->format('Y-m-d')));

        $this->repository   = app(AvailableBudgetRepositoryInterface::class);
        $this->blRepository = app(BudgetLimitRepositoryInterface::class);
        $this->repository->setUser($user);
        $this->blRepository->setUser($user);
        // based on the view range of the user (month week quarter etc) the budget limit could
        // either overlap multiple available budget periods or be contained in a single one.
        // all have to be created or updated.
        $viewRange = Preferences::getForUser($user, 'viewRange', '1M')->data;
        $viewRange = !is_string($viewRange) ? '1M' : $viewRange;
        $viewRange = $this->correctViewRange($viewRange);

        $start = Navigation::startOfPeriod($start, $viewRange);
        $end   = Navigation::startOfPeriod($end, $viewRange);
        $end   = Navigation::endOfPeriod($end, $viewRange);

        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }
        Log::debug(sprintf('Corrected start to %s and end to %s.', $start->format('Y-m-d'), $end->format('Y-m-d')));

        // limit period in total is:
        $limitPeriod = Period::make($start, $end, precision: Precision::DAY(), boundaries: Boundaries::EXCLUDE_NONE());
        Log::debug(sprintf('Limit period is from %s to %s', $start->format('Y-m-d'), $end->format('Y-m-d')));

        // from the start until the end of the budget limit, need to loop!
        $current = clone $start;
        while ($current <= $end) {
            $this->refreshAvailableBudget($current, $viewRange);
            $current = Navigation::addPeriod($current, $viewRange);
        }
    }


}
