<?php


/*
 * BudgetLimitHandler.php
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

namespace FireflyIII\Handlers\Events\Model;

use FireflyIII\Events\Model\BudgetLimit\Created;
use FireflyIII\Events\Model\BudgetLimit\Deleted;
use FireflyIII\Events\Model\BudgetLimit\Updated;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\Precision;

/**
 * Class BudgetLimitHandler
 */
class BudgetLimitHandler
{
    /**
     * @param Created $event
     *
     * @return void
     */
    public function created(Created $event): void
    {
        Log::debug(sprintf('BudgetLimitHandler::created(#%s)', $event->budgetLimit->id));
        $this->updateAvailableBudget($event->budgetLimit);
    }

    /**
     * @param BudgetLimit $budgetLimit
     *
     * @return void
     */
    private function updateAvailableBudget(BudgetLimit $budgetLimit): void
    {
        Log::debug(sprintf('Now in updateAvailableBudget(#%d)', $budgetLimit->id));

        // based on the view range of the user (month week quarter etc) the budget limit could
        // either overlap multiple available budget periods or be contained in a single one.
        // all have to be created or updated.
        try {
            $viewRange = app('preferences')->get('viewRange', '1M')->data;
        } catch (ContainerExceptionInterface | NotFoundExceptionInterface $e) {
            $viewRange = '1M';
        }
        $start  = app('navigation')->startOfPeriod($budgetLimit->start_date, $viewRange);
        $end    = app('navigation')->startOfPeriod($budgetLimit->end_date, $viewRange);
        $end    = app('navigation')->endOfPeriod($end, $viewRange);
        $budget = Budget::find($budgetLimit->budget_id);
        if (null === $budget) {
            Log::warning('Budget is null, probably deleted, find deleted version.');
            $budget = Budget::withTrashed()->find($budgetLimit->budget_id);
        }
        if (null === $budget) {
            Log::warning('Budget is still null, cannot continue, will delete budget limit.');
            $budgetLimit->forceDelete();
            return;
        }
        $user = $budget->user;

        // sanity check. It happens when the budget has been deleted so the original user is unknown.
        if (null === $user) {
            Log::warning('User is null, cannot continue.');
            $budgetLimit->forceDelete();
            return;
        }

        // limit period in total is:
        $limitPeriod = Period::make($start, $end, precision: Precision::DAY(), boundaries: Boundaries::EXCLUDE_NONE());

        // from the start until the end of the budget limit, need to loop!
        $current = clone $start;
        while ($current <= $end) {
            $currentEnd = app('navigation')->endOfPeriod($current, $viewRange);

            // create or find AB for this particular period, and set the amount accordingly.
            /** @var AvailableBudget $availableBudget */
            $availableBudget = $user->availableBudgets()->where('start_date', $current->format('Y-m-d'))->where(
                'end_date',
                $currentEnd->format('Y-m-d')
            )->where('transaction_currency_id', $budgetLimit->transaction_currency_id)->first();

            if (null !== $availableBudget) {
                Log::debug('Found 1 AB, will update.');
                $this->calculateAmount($availableBudget);
            }
            if (null === $availableBudget) {
                // if not exists:
                $currentPeriod = Period::make($current, $currentEnd, precision: Precision::DAY(), boundaries: Boundaries::EXCLUDE_NONE());
                $daily         = $this->getDailyAmount($budgetLimit);
                $amount        = bcmul($daily, (string)$currentPeriod->length(), 12);

                // no need to calculate if period is equal.
                if ($currentPeriod->equals($limitPeriod)) {
                    $amount = 0 === (int)$budgetLimit->id ? '0' : $budgetLimit->amount;
                }
                if (0 === bccomp($amount, '0')) {
                    Log::debug('Amount is zero, will not create AB.');
                }
                if (0 !== bccomp($amount, '0')) {
                    Log::debug(sprintf('Will create AB for period %s to %s', $current->format('Y-m-d'), $currentEnd->format('Y-m-d')));
                    $availableBudget = new AvailableBudget(
                        [
                            'user_id'                 => $budgetLimit->budget->user->id,
                            'user_group_id'           => $budgetLimit->budget->user->user_group_id,
                            'transaction_currency_id' => $budgetLimit->transaction_currency_id,
                            'start_date'              => $current,
                            'end_date'                => $currentEnd,
                            'amount'                  => $amount,
                        ]
                    );
                    $availableBudget->save();
                }
            }

            // prep for next loop
            $current = app('navigation')->addPeriod($current, $viewRange, 0);
        }
    }

    /**
     * @param AvailableBudget $availableBudget
     *
     * @return void
     */
    private function calculateAmount(AvailableBudget $availableBudget): void
    {
        $repository = app(BudgetLimitRepositoryInterface::class);
        $repository->setUser($availableBudget->user);
        $newAmount = '0';
        $abPeriod  = Period::make($availableBudget->start_date, $availableBudget->end_date, Precision::DAY());
        Log::debug(
            sprintf(
                'Now at AB #%d, ("%s" to "%s")',
                $availableBudget->id,
                $availableBudget->start_date->format('Y-m-d'),
                $availableBudget->end_date->format('Y-m-d')
            )
        );
        // have to recalc everything just in case.
        $set = $repository->getAllBudgetLimitsByCurrency($availableBudget->transactionCurrency, $availableBudget->start_date, $availableBudget->end_date);
        Log::debug(sprintf('Found %d interesting budget limit(s).', $set->count()));
        /** @var BudgetLimit $budgetLimit */
        foreach ($set as $budgetLimit) {
            Log::debug(
                sprintf(
                    'Found interesting budget limit #%d ("%s" to "%s")',
                    $budgetLimit->id,
                    $budgetLimit->start_date->format('Y-m-d'),
                    $budgetLimit->end_date->format('Y-m-d')
                )
            );
            // overlap in days:
            $limitPeriod = Period::make(
                $budgetLimit->start_date,
                $budgetLimit->end_date,
                precision : Precision::DAY(),
                boundaries: Boundaries::EXCLUDE_NONE()
            );
            // if both equal eachother, amount from this BL must be added to the AB
            if ($limitPeriod->equals($abPeriod)) {
                $newAmount = bcadd($newAmount, $budgetLimit->amount);
            }
            // if budget limit period inside AB period, can be added in full.
            if (!$limitPeriod->equals($abPeriod) && $abPeriod->contains($limitPeriod)) {
                $newAmount = bcadd($newAmount, $budgetLimit->amount);
            }
            if (!$limitPeriod->equals($abPeriod) && $abPeriod->overlapsWith($limitPeriod)) {
                $overlap = $abPeriod->overlap($limitPeriod);
                if (null !== $overlap) {
                    $length    = $overlap->length();
                    $daily     = bcmul($this->getDailyAmount($budgetLimit), (string)$length);
                    $newAmount = bcadd($newAmount, $daily);
                }
            }
        }
        if (0 === bccomp('0', $newAmount)) {
            Log::debug('New amount is zero, deleting AB.');
            $availableBudget->delete();
            return;
        }
        Log::debug(sprintf('Concluded new amount for this AB must be %s', $newAmount));
        $availableBudget->amount = $newAmount;
        $availableBudget->save();
    }

    /**
     * @param BudgetLimit $budgetLimit
     *
     * @return string
     */
    private function getDailyAmount(BudgetLimit $budgetLimit): string
    {
        if (0 === (int)$budgetLimit->id) {
            return '0';
        }
        $limitPeriod = Period::make(
            $budgetLimit->start_date,
            $budgetLimit->end_date,
            precision : Precision::DAY(),
            boundaries: Boundaries::EXCLUDE_NONE()
        );
        $days        = $limitPeriod->length();
        $amount      = bcdiv((string)$budgetLimit->amount, (string)$days, 12);
        Log::debug(
            sprintf('Total amount for budget limit #%d is %s. Nr. of days is %d. Amount per day is %s', $budgetLimit->id, $budgetLimit->amount, $days, $amount)
        );
        return $amount;
    }

    /**
     * @param Deleted $event
     *
     * @return void
     */
    public function deleted(Deleted $event): void
    {
        Log::debug(sprintf('BudgetLimitHandler::deleted(#%s)', $event->budgetLimit->id));
        $budgetLimit     = $event->budgetLimit;
        $budgetLimit->id = null;
        $this->updateAvailableBudget($event->budgetLimit);
    }

    /**
     * @param Updated $event
     *
     * @return void
     */
    public function updated(Updated $event): void
    {
        Log::debug(sprintf('BudgetLimitHandler::updated(#%s)', $event->budgetLimit->id));
        $this->updateAvailableBudget($event->budgetLimit);
    }

}
