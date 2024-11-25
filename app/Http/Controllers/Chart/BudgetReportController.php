<?php

/**
 * BudgetReportController.php
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

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Support\Http\Controllers\AugumentData;
use FireflyIII\Support\Http\Controllers\TransactionCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Separate controller because many helper functions are shared.
 *
 * Class BudgetReportController
 */
class BudgetReportController extends Controller
{
    use AugumentData;
    use TransactionCalculation;

    /** @var GeneratorInterface Chart generation methods. */
    private $generator;

    /** @var OperationsRepositoryInterface */
    private $opsRepository;

    /**
     * BudgetReportController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->generator     = app(GeneratorInterface::class);
                $this->opsRepository = app(OperationsRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Chart that groups the expenses by budget.
     */
    public function budgetExpense(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $budgets);

        // loop expenses.
        foreach ($spent as $currency) {
            foreach ($currency['budgets'] as $budget) {
                $title = sprintf('%s (%s)', $budget['name'], $currency['currency_name']);
                $result[$title] ??= [
                    'amount'          => '0',
                    'currency_symbol' => $currency['currency_symbol'],
                    'currency_code'   => $currency['currency_code'],
                ];
                foreach ($budget['transaction_journals'] as $journal) {
                    $amount                   = app('steam')->positive($journal['amount']);
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
                }
            }
        }

        $data   = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    /**
     * Chart that groups the expenses by budget.
     */
    public function categoryExpense(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $budgets);
        // loop expenses.
        foreach ($spent as $currency) {
            foreach ($currency['budgets'] as $budget) {
                foreach ($budget['transaction_journals'] as $journal) {
                    $categoryName             = $journal['category_name'] ?? trans('firefly.no_category');
                    $title                    = sprintf('%s (%s)', $categoryName, $currency['currency_name']);
                    $result[$title] ??= [
                        'amount'          => '0',
                        'currency_symbol' => $currency['currency_symbol'],
                        'currency_code'   => $currency['currency_code'],
                    ];

                    $amount                   = app('steam')->positive($journal['amount']);
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
                }
            }
        }

        $data   = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    /**
     * Chart that groups expenses by the account.
     */
    public function destinationAccountExpense(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $budgets);

        // loop expenses.
        foreach ($spent as $currency) {
            foreach ($currency['budgets'] as $budget) {
                foreach ($budget['transaction_journals'] as $journal) {
                    $title                    = sprintf('%s (%s)', $journal['destination_account_name'], $currency['currency_name']);
                    $result[$title] ??= [
                        'amount'          => '0',
                        'currency_symbol' => $currency['currency_symbol'],
                        'currency_code'   => $currency['currency_code'],
                    ];

                    $amount                   = app('steam')->positive($journal['amount']);
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
                }
            }
        }

        $data   = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    /**
     * Main overview of a budget in the budget report.
     */
    public function mainChart(Collection $accounts, Budget $budget, Carbon $start, Carbon $end): JsonResponse
    {
        $chartData = [];
        $spent     = $this->opsRepository->listExpenses($start, $end, $accounts, new Collection([$budget]));
        $format    = app('navigation')->preferredCarbonLocalizedFormat($start, $end);

        // loop expenses.
        foreach ($spent as $currency) {
            // add things to chart Data for each currency:
            $spentKey = sprintf('%d-spent', $currency['currency_id']);
            $chartData[$spentKey] ??= [
                'label'           => sprintf(
                    '%s (%s)',
                    (string)trans('firefly.spent_in_specific_budget', ['budget' => $budget->name]),
                    $currency['currency_name']
                ),
                'type'            => 'bar',
                'currency_symbol' => $currency['currency_symbol'],
                'currency_code'   => $currency['currency_code'],
                'currency_id'     => $currency['currency_id'],
                'entries'         => $this->makeEntries($start, $end),
            ];

            foreach ($currency['budgets'] as $currentBudget) {
                foreach ($currentBudget['transaction_journals'] as $journal) {
                    $key                                   = $journal['date']->isoFormat($format);
                    $amount                                = app('steam')->positive($journal['amount']);
                    $chartData[$spentKey]['entries'][$key] ??= '0';
                    $chartData[$spentKey]['entries'][$key] = bcadd($chartData[$spentKey]['entries'][$key], $amount);
                }
            }
        }

        $data      = $this->generator->multiSet($chartData);

        return response()->json($data);
    }

    private function makeEntries(Carbon $start, Carbon $end): array
    {
        $return         = [];
        $format         = app('navigation')->preferredCarbonLocalizedFormat($start, $end);
        $preferredRange = app('navigation')->preferredRangeFormat($start, $end);
        $currentStart   = clone $start;
        while ($currentStart <= $end) {
            $currentEnd   = app('navigation')->endOfPeriod($currentStart, $preferredRange);
            $key          = $currentStart->isoFormat($format);
            $return[$key] = '0';
            $currentStart = clone $currentEnd;
            $currentStart->addDay()->startOfDay();
        }

        return $return;
    }

    /**
     * Chart that groups expenses by the account.
     */
    public function sourceAccountExpense(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $budgets);

        // loop expenses.
        foreach ($spent as $currency) {
            foreach ($currency['budgets'] as $budget) {
                foreach ($budget['transaction_journals'] as $journal) {
                    $title                    = sprintf('%s (%s)', $journal['source_account_name'], $currency['currency_name']);
                    $result[$title] ??= [
                        'amount'          => '0',
                        'currency_symbol' => $currency['currency_symbol'],
                        'currency_code'   => $currency['currency_code'],
                    ];

                    $amount                   = app('steam')->positive($journal['amount']);
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
                }
            }
        }

        $data   = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }
}
