<?php

/**
 * FrontpageChartGenerator.php
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

namespace FireflyIII\Support\Chart\Budget;

use Carbon\Carbon;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class FrontpageChartGenerator
 */
class FrontpageChartGenerator
{
    protected OperationsRepositoryInterface $opsRepository;
    private BudgetLimitRepositoryInterface  $blRepository;
    private BudgetRepositoryInterface       $budgetRepository;
    private Carbon                          $end;
    private string                          $monthAndDayFormat;
    private Carbon                          $start;

    /**
     * FrontpageChartGenerator constructor.
     */
    public function __construct()
    {
        $this->budgetRepository  = app(BudgetRepositoryInterface::class);
        $this->blRepository      = app(BudgetLimitRepositoryInterface::class);
        $this->opsRepository     = app(OperationsRepositoryInterface::class);
        $this->monthAndDayFormat = '';
    }

    /**
     * Generate the data for a budget chart. Collect all budgets and process each budget.
     *
     * @return array[]
     */
    public function generate(): array
    {
        $budgets = $this->budgetRepository->getActiveBudgets();
        $data    = [
            ['label' => (string) trans('firefly.spent_in_budget'), 'entries' => [], 'type' => 'bar'],
            ['label' => (string) trans('firefly.left_to_spend'), 'entries' => [], 'type' => 'bar'],
            ['label' => (string) trans('firefly.overspent'), 'entries' => [], 'type' => 'bar'],
        ];

        // loop al budgets:
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $data = $this->processBudget($data, $budget);
        }

        return $data;
    }

    /**
     * For each budget, gets all budget limits for the current time range.
     * When no limits are present, the time range is used to collect information on money spent.
     * If limits are present, each limit is processed individually.
     */
    private function processBudget(array $data, Budget $budget): array
    {
        // get all limits:
        $limits = $this->blRepository->getBudgetLimits($budget, $this->start, $this->end);

        // if no limits
        if (0 === $limits->count()) {
            return $this->noBudgetLimits($data, $budget);
        }

        return $this->budgetLimits($data, $budget, $limits);
    }

    /**
     * When no limits are present, the expenses of the whole period are collected and grouped.
     * This is grouped per currency. Because there is no limit set, "left to spend" and "overspent" are empty.
     */
    private function noBudgetLimits(array $data, Budget $budget): array
    {
        $spent = $this->opsRepository->sumExpenses($this->start, $this->end, null, new Collection([$budget]));

        /** @var array $entry */
        foreach ($spent as $entry) {
            $title                      = sprintf('%s (%s)', $budget->name, $entry['currency_name']);
            $data[0]['entries'][$title] = bcmul($entry['sum'], '-1'); // spent
            $data[1]['entries'][$title] = 0;                          // left to spend
            $data[2]['entries'][$title] = 0;                          // overspent
        }

        return $data;
    }

    /**
     * If a budget has budget limit, each limit is processed individually.
     */
    private function budgetLimits(array $data, Budget $budget, Collection $limits): array
    {
        /** @var BudgetLimit $limit */
        foreach ($limits as $limit) {
            $data = $this->processLimit($data, $budget, $limit);
        }

        return $data;
    }

    /**
     * For each limit, the expenses from the time range of the limit are collected. Each row from the result is
     * processed individually.
     */
    private function processLimit(array $data, Budget $budget, BudgetLimit $limit): array
    {
        $spent = $this->opsRepository->sumExpenses($limit->start_date, $limit->end_date, null, new Collection([$budget]), $limit->transactionCurrency);

        /** @var array $entry */
        foreach ($spent as $entry) {
            // only spent the entry where the entry's currency matches the budget limit's currency
            if ($entry['currency_id'] === $limit->transaction_currency_id) {
                $data = $this->processRow($data, $budget, $limit, $entry);
            }
        }

        return $data;
    }

    /**
     * Each row of expenses from a budget limit is in another currency (note $entry['currency_name']).
     *
     * Each one is added to the $data array. If the limit's date range is different from the global $start and $end
     * dates, for example when a limit only partially falls into this month, the title is expanded to clarify.
     */
    private function processRow(array $data, Budget $budget, BudgetLimit $limit, array $entry): array
    {
        $title = sprintf('%s (%s)', $budget->name, $entry['currency_name']);
        if ($limit->start_date->startOfDay()->ne($this->start->startOfDay()) || $limit->end_date->startOfDay()->ne($this->end->startOfDay())) {
            $title = sprintf(
                '%s (%s) (%s - %s)',
                $budget->name,
                $entry['currency_name'],
                $limit->start_date->isoFormat($this->monthAndDayFormat),
                $limit->end_date->isoFormat($this->monthAndDayFormat)
            );
        }
        $sumSpent = bcmul($entry['sum'], '-1'); // spent

        $data[0]['entries'][$title] = 1 === bccomp($sumSpent, $limit->amount) ? $limit->amount : $sumSpent;                              // spent
        $data[1]['entries'][$title] = 1 === bccomp($limit->amount, $sumSpent) ? bcadd($entry['sum'], $limit->amount) : '0';              // left to spent
        $data[2]['entries'][$title] = 1 === bccomp($limit->amount, $sumSpent) ? '0' : bcmul(bcadd($entry['sum'], $limit->amount), '-1'); // overspent

        return $data;
    }

    public function setEnd(Carbon $end): void
    {
        $this->end = $end;
    }

    public function setStart(Carbon $start): void
    {
        $this->start = $start;
    }

    /**
     * A basic setter for the user. Also updates the repositories with the right user.
     */
    public function setUser(User $user): void
    {
        $this->budgetRepository->setUser($user);
        $this->blRepository->setUser($user);
        $this->opsRepository->setUser($user);

        $locale                  = app('steam')->getLocale();
        $this->monthAndDayFormat = (string) trans('config.month_and_day_js', [], $locale);
    }
}
