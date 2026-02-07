<?php

declare(strict_types=1);

/*
 * AvailableBudgetCalculator.php
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

namespace FireflyIII\Support\Models;

use Carbon\Carbon;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Support\Facades\Navigation;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\User;
use Illuminate\Support\Facades\Log;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\Precision;

class AvailableBudgetCalculator
{
    private User $user;
    private bool $create = true;
    private Carbon $start;
    private Carbon $end;
    private string $viewRange;
    private TransactionCurrency $currency;

    private AvailableBudgetRepositoryInterface $abRepository;
    private BudgetLimitRepositoryInterface $blRepository;

    public function recalculateByRange(): void
    {
        Log::debug(sprintf('Now in recalculateByRange(%s, %s)', $this->start->format('Y-m-d'), $this->start->format('Y-m-d')));
        // based on the view range of the user (month week quarter etc) the budget limit could
        // either overlap multiple available budget periods or be contained in a single one.
        // all have to be created or updated.
        $start       = Navigation::startOfPeriod($this->start, $this->viewRange);
        $end         = Navigation::startOfPeriod($this->end, $this->viewRange);
        $end         = Navigation::endOfPeriod($end, $this->viewRange);

        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }
        Log::debug(sprintf('Corrected start to %s and end to %s.', $start->format('Y-m-d'), $end->format('Y-m-d')));

        // limit period in total is:
        $limitPeriod = Period::make($start, $end, precision: Precision::DAY(), boundaries: Boundaries::EXCLUDE_NONE());
        Log::debug(sprintf('Limit period is from %s to %s', $start->format('Y-m-d'), $end->format('Y-m-d')));

        // from the start until the end of the budget limit, need to loop!
        $current     = clone $start;
        while ($current <= $end) {
            $this->refreshAvailableBudget($current);
            $current = Navigation::addPeriod($current, $this->viewRange);
        }
    }

    public function setCreate(bool $create): void
    {
        $this->create = $create;
    }

    public function setCurrency(TransactionCurrency $currency): void
    {
        $this->currency = $currency;
    }

    public function setEnd(Carbon $end): void
    {
        $this->end = $end;
    }

    public function setStart(Carbon $start): void
    {
        $this->start = $start;
    }

    public function setUser(User $user): void
    {
        $this->user         = $user;
        $this->abRepository = app(AvailableBudgetRepositoryInterface::class);
        $this->blRepository = app(BudgetLimitRepositoryInterface::class);
        $this->abRepository->setUser($user);
        $this->blRepository->setUser($user);

        $viewRange          = Preferences::getForUser($user, 'viewRange', '1M')->data;
        $viewRange          = !is_string($viewRange) ? '1M' : $viewRange;
        $this->viewRange    = $this->correctViewRange($viewRange);
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

    private function refreshAvailableBudget(Carbon $start): void
    {
        $end             = Navigation::endOfPeriod($start, $this->viewRange);
        Log::debug(sprintf('refreshAvailableBudget(%s), end is %s', $start->format('Y-m-d'), $end->format('Y-m-d')));
        $availableBudget = $this->abRepository->find($this->currency, $start, $end);

        if (null !== $availableBudget) {
            Log::debug('Found available budget for this period, will update it.');
            $this->abRepository->recalculateAmount($availableBudget);

            return;
        }
        if (!$this->create) {
            Log::debug('Can stop here. have not been asked to create an available budget.');

            return;
        }
        if ($end->lt($start)) {
            Log::error(sprintf('%s is less than %s, stop.', $start->format('Y-m-d'), $end->format('Y-m-d')));

            return;
        }
        Log::debug(sprintf('Will create new available budget for period %s to %s', $start->format('Y-m-d'), $end->format('Y-m-d')));
        $availableBudget = $this->abRepository->store([
            'start'       => $start,
            'end'         => $end,
            'currency_id' => $this->currency->id,
            'amount'      => '1',
        ]);
        $this->abRepository->recalculateAmount($availableBudget);
    }
}
