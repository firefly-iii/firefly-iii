<?php
/**
 * ExpenseReportController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\AugumentData;
use FireflyIII\Support\Http\Controllers\TransactionCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Separate controller because many helper functions are shared.
 *
 * Class ExpenseReportController
 */
class ExpenseReportController extends Controller
{
    use AugumentData, TransactionCalculation;
    /** @var AccountRepositoryInterface The account repository */
    protected $accountRepository;
    /** @var GeneratorInterface Chart generation methods. */
    protected $generator;

    /**
     * ExpenseReportController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->generator         = app(GeneratorInterface::class);
                $this->accountRepository = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }


    /**
     * Main chart that shows income and expense for a combination of expense/revenue accounts.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Collection $expense
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return JsonResponse
     *
     */
    public function mainChart(Collection $accounts, Collection $expense, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.expense.report.main');
        $cache->addProperty($accounts);
        $cache->addProperty($expense);
        $cache->addProperty($start);
        $cache->addProperty($end);
        if ($cache->has()) {
             return response()->json($cache->get()); // @codeCoverageIgnore
        }

        $format       = app('navigation')->preferredCarbonLocalizedFormat($start, $end);
        $function     = app('navigation')->preferredEndOfPeriod($start, $end);
        $chartData    = [];
        $currentStart = clone $start;
        $combined     = $this->combineAccounts($expense);
        // make "all" set:
        $all = new Collection;
        foreach ($combined as $name => $combination) {
            $all = $all->merge($combination);
        }

        // prep chart data:
        /**
         * @var string $name
         * @var Collection $combination
         */
        foreach ($combined as $name => $combination) {
            // first is always expense account:
            /** @var Account $exp */
            $exp                          = $combination->first();
            $chartData[$exp->id . '-in']  = [
                'label'   => sprintf('%s (%s)', $name, strtolower((string)trans('firefly.income'))),
                'type'    => 'bar',
                'yAxisID' => 'y-axis-0',
                'entries' => [],
            ];
            $chartData[$exp->id . '-out'] = [
                'label'   => sprintf('%s (%s)', $name, strtolower((string)trans('firefly.expenses'))),
                'type'    => 'bar',
                'yAxisID' => 'y-axis-0',
                'entries' => [],
            ];
            // total in, total out:
            $chartData[$exp->id . '-total-in']  = [
                'label'   => sprintf('%s (%s)', $name, strtolower((string)trans('firefly.sum_of_income'))),
                'type'    => 'line',
                'fill'    => false,
                'yAxisID' => 'y-axis-1',
                'entries' => [],
            ];
            $chartData[$exp->id . '-total-out'] = [
                'label'   => sprintf('%s (%s)', $name, strtolower((string)trans('firefly.sum_of_expenses'))),
                'type'    => 'line',
                'fill'    => false,
                'yAxisID' => 'y-axis-1',
                'entries' => [],
            ];
        }

        $sumOfIncome  = [];
        $sumOfExpense = [];

        while ($currentStart < $end) {
            $currentEnd = clone $currentStart;
            $currentEnd = $currentEnd->$function();

            // get expenses grouped by opposing name:
            $expenses = $this->groupByName($this->getExpensesForOpposing($accounts, $all, $currentStart, $currentEnd));
            $income   = $this->groupByName($this->getIncomeForOpposing($accounts, $all, $currentStart, $currentEnd));
            $label    = $currentStart->formatLocalized($format);

            foreach ($combined as $name => $combination) {
                // first is always expense account:
                /** @var Account $exp */
                $exp            = $combination->first();
                $labelIn        = $exp->id . '-in';
                $labelOut       = $exp->id . '-out';
                $labelSumIn     = $exp->id . '-total-in';
                $labelSumOut    = $exp->id . '-total-out';
                $currentIncome  = bcmul($income[$name] ?? '0', '-1');
                $currentExpense = $expenses[$name] ?? '0';

                // add to sum:
                $sumOfIncome[$exp->id]  = $sumOfIncome[$exp->id] ?? '0';
                $sumOfExpense[$exp->id] = $sumOfExpense[$exp->id] ?? '0';
                $sumOfIncome[$exp->id]  = bcadd($sumOfIncome[$exp->id], $currentIncome);
                $sumOfExpense[$exp->id] = bcadd($sumOfExpense[$exp->id], $currentExpense);

                // add to chart:
                $chartData[$labelIn]['entries'][$label]     = $currentIncome;
                $chartData[$labelOut]['entries'][$label]    = $currentExpense;
                $chartData[$labelSumIn]['entries'][$label]  = $sumOfIncome[$exp->id];
                $chartData[$labelSumOut]['entries'][$label] = $sumOfExpense[$exp->id];
            }
            /** @var Carbon $currentStart */
            $currentStart = clone $currentEnd;
            $currentStart->addDay();
            $currentStart->startOfDay();
        }
        // remove all empty entries to prevent cluttering:
        $newSet = [];
        foreach ($chartData as $key => $entry) {
            if (0 === !array_sum($entry['entries'])) {
                $newSet[$key] = $chartData[$key]; // @codeCoverageIgnore
            }
        }
        if (0 === count($newSet)) {
            $newSet = $chartData; // @codeCoverageIgnore
        }
        $data = $this->generator->multiSet($newSet);
        $cache->store($data);

        return response()->json($data);
    }
}
