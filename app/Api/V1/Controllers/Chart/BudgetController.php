<?php
/**
 * BudgetController.php
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

namespace FireflyIII\Api\V1\Controllers\Chart;


use Carbon\Carbon;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\DateRequest;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class BudgetController
 */
class BudgetController extends Controller
{
    private BudgetLimitRepositoryInterface $blRepository;

    private OperationsRepositoryInterface  $opsRepository;

    private BudgetRepositoryInterface      $repository;


    /**
     * BudgetController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                $this->repository    = app(BudgetRepositoryInterface::class);
                $this->opsRepository = app(OperationsRepositoryInterface::class);
                $this->blRepository  = app(BudgetLimitRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * [
     *   'label'                   => 'label for entire set'
     *   'currency_id'             => 0,
     *   'currency_code'           => 'EUR',
     *   'currency_symbol'         => '$',
     *   'currency_decimal_places' => 2,
     *   'type'                    => 'bar', // line, area or bar
     *   'yAxisID'                 => 0, // 0, 1, 2
     *   'entries'                 => ['a' => 1, 'b' => 4],
     * ],
     *
     * @param DateRequest $request
     *
     * @return JsonResponse
     */
    public function overview(DateRequest $request): JsonResponse
    {
        $dates         = $request->getAll();
        $budgets       = $this->repository->getActiveBudgets();
        $budgetNames   = [];
        $currencyNames = [];
        $sets          = [];
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $expenses = $this->getExpenses($budget, $dates['start'], $dates['end']);
            $expenses = $this->filterNulls($expenses);
            foreach ($expenses as $set) {
                $budgetNames[]   = $set['budget_name'];
                $currencyNames[] = $set['currency_name'];
                $sets[]          = $set;
            }
        }
        $budgetNames   = array_unique($budgetNames);
        $currencyNames = array_unique($currencyNames);
        $basic         = $this->createSets($budgetNames, $currencyNames);
        $filled        = $this->fillSets($basic, $sets);
        $keys          = array_values($filled);

        return response()->json($keys);
    }

    /**
     * @param Collection $limits
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    protected function getExpenses(Budget $budget, Carbon $start, Carbon $end): array
    {
        $limits = $this->blRepository->getBudgetLimits($budget, $start, $end);
        if (0 === $limits->count()) {
            return $this->getExpenseInRange($budget, $start, $end);
        }
        $arr = [];
        /** @var BudgetLimit $limit */
        foreach ($limits as $limit) {
            $arr[] = $this->getExpensesForLimit($limit);
        }

        return $arr;
    }

    /**
     * @param array $budgetNames
     * @param array $currencyNames
     *
     * @return array
     */
    private function createSets(array $budgetNames, array $currencyNames): array
    {
        $return = [];
        foreach ($currencyNames as $currencyName) {
            $entries = [];
            foreach ($budgetNames as $budgetName) {
                $label           = sprintf('%s (%s)', $budgetName, $currencyName);
                $entries[$label] = '0';
            }

            // left
            $return['left'] = [
                'label'         => sprintf('%s (%s)', trans('firefly.left'), $currencyName),
                'data_type'     => 'left',
                'currency_name' => $currencyName,
                'type'          => 'bar',
                'yAxisID'       => 0, // 0, 1, 2
                'entries'       => $entries,
            ];

            // spent_capped
            $return['spent_capped'] = [
                'label'         => sprintf('%s (%s)', trans('firefly.spent'), $currencyName),
                'data_type'     => 'spent_capped',
                'currency_name' => $currencyName,
                'type'          => 'bar',
                'yAxisID'       => 0, // 0, 1, 2
                'entries'       => $entries,
            ];

            // overspent
            $return['overspent'] = [
                'label'         => sprintf('%s (%s)', trans('firefly.overspent'), $currencyName),
                'data_type'     => 'overspent',
                'currency_name' => $currencyName,
                'type'          => 'bar',
                'yAxisID'       => 0, // 0, 1, 2
                'entries'       => $entries,
            ];

        }

        return $return;
    }

    /**
     * @param array $basic
     * @param array $sets
     *
     * @return array
     */
    private function fillSets(array $basic, array $sets): array
    {
        foreach ($sets as $set) {
            $label                                    = $set['label'];
            $basic['spent_capped']['entries'][$label] = $set['entries']['spent_capped'];
            $basic['left']['entries'][$label]         = $set['entries']['left'];
            $basic['overspent']['entries'][$label]    = $set['entries']['overspent'];
        }

        return $basic;
    }

    /**
     * @param array $expenses
     *
     * @return array
     */
    private function filterNulls(array $expenses): array
    {
        $return = [];
        /** @var array|null $arr */
        foreach ($expenses as $arr) {
            if ([] !== $arr) {
                $return[] = $arr;
            }
        }

        return $return;
    }

    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    private function getExpenseInRange(Budget $budget, Carbon $start, Carbon $end): array
    {
        $spent  = $this->opsRepository->sumExpenses($start, $end, null, new Collection([$budget]), null);
        $return = [];
        /** @var array $set */
        foreach ($spent as $set) {
            $current                            = [
                'label'                   => sprintf('%s (%s)', $budget->name, $set['currency_name']),
                'budget_name'             => $budget->name,
                'start_date'              => $start->format('Y-m-d'),
                'end_date'                => $end->format('Y-m-d'),
                'currency_id'             => (int) $set['currency_id'],
                'currency_code'           => $set['currency_code'],
                'currency_name'           => $set['currency_name'],
                'currency_symbol'         => $set['currency_symbol'],
                'currency_decimal_places' => (int) $set['currency_decimal_places'],
                'type'                    => 'bar', // line, area or bar,
                'entries'                 => [],
            ];
            $sumSpent                           = bcmul($set['sum'], '-1'); // spent
            $current['entries']['spent']        = $sumSpent;
            $current['entries']['amount']       = '0';
            $current['entries']['spent_capped'] = $sumSpent;
            $current['entries']['left']         = '0';
            $current['entries']['overspent']    = '0';
            $return[]                           = $current;
        }

        return $return;
    }

    /**
     * @param BudgetLimit $limit
     *
     * @return array
     */
    private function getExpensesForLimit(BudgetLimit $limit): array
    {
        $budget   = $limit->budget;
        $spent    = $this->opsRepository->sumExpenses($limit->start_date, $limit->end_date, null, new Collection([$budget]), $limit->transactionCurrency);
        $currency = $limit->transactionCurrency;
        // when limited to a currency, the count is always one. Or it's empty.
        $set = array_shift($spent);
        if (null === $set) {
            return [];
        }
        $return                            = [
            'label'                   => sprintf('%s (%s)', $budget->name, $set['currency_name']),
            'budget_name'             => $budget->name,
            'start_date'              => $limit->start_date->format('Y-m-d'),
            'end_date'                => $limit->end_date->format('Y-m-d'),
            'currency_id'             => (int) $currency->id,
            'currency_code'           => $currency->code,
            'currency_name'           => $currency->name,
            'currency_symbol'         => $currency->symbol,
            'currency_decimal_places' => (int) $currency->decimal_places,
            'type'                    => 'bar', // line, area or bar,
            'entries'                 => [],
        ];
        $sumSpent                          = bcmul($set['sum'], '-1'); // spent
        $return['entries']['spent']        = $sumSpent;
        $return['entries']['amount']       = $limit->amount;
        $return['entries']['spent_capped'] = 1 === bccomp($sumSpent, $limit->amount) ? $limit->amount : $sumSpent;
        $return['entries']['left']         = 1 === bccomp($limit->amount, $sumSpent) ? bcadd($set['sum'], $limit->amount) : '0'; // left
        $return['entries']['overspent']    = 1 === bccomp($limit->amount, $sumSpent) ? '0' : bcmul(bcadd($set['sum'], $limit->amount), '-1'); // overspent

        return $return;
    }

}
