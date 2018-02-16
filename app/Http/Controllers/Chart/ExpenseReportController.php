<?php
/**
 * ExpenseReportController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Response;

/**
 * Separate controller because many helper functions are shared.
 *
 * Class ExpenseReportController
 */
class ExpenseReportController extends Controller
{
    /** @var AccountRepositoryInterface */
    protected $accountRepository;
    /** @var GeneratorInterface */
    protected $generator;

    /**
     *
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
     * @param Collection $accounts
     * @param Collection $expense
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function mainChart(Collection $accounts, Collection $expense, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.expense.report.main');
        $cache->addProperty($accounts);
        $cache->addProperty($expense);
        $cache->addProperty($start);
        $cache->addProperty($end);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $format       = app('navigation')->preferredCarbonLocalizedFormat($start, $end);
        $function     = app('navigation')->preferredEndOfPeriod($start, $end);
        $chartData    = [];
        $currentStart = clone $start;
        $combined     = $this->combineAccounts($expense);

        // make "all" set:
        $all = new Collection;
        foreach ($combined as $name => $combi) {
            $all = $all->merge($combi);
        }

        // prep chart data:
        foreach ($combined as $name => $combi) {
            // first is always expense account:
            /** @var Account $exp */
            $exp                          = $combi->first();
            $chartData[$exp->id . '-in']  = [
                'label'   => $name . ' (' . strtolower(strval(trans('firefly.income'))) . ')',
                'type'    => 'bar',
                'yAxisID' => 'y-axis-0',
                'entries' => [],
            ];
            $chartData[$exp->id . '-out'] = [
                'label'   => $name . ' (' . strtolower(strval(trans('firefly.expenses'))) . ')',
                'type'    => 'bar',
                'yAxisID' => 'y-axis-0',
                'entries' => [],
            ];
            // total in, total out:
            $chartData[$exp->id . '-total-in']  = [
                'label'   => $name . ' (' . strtolower(strval(trans('firefly.sum_of_income'))) . ')',
                'type'    => 'line',
                'fill'    => false,
                'yAxisID' => 'y-axis-1',
                'entries' => [],
            ];
            $chartData[$exp->id . '-total-out'] = [
                'label'   => $name . ' (' . strtolower(strval(trans('firefly.sum_of_expenses'))) . ')',
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
            $expenses = $this->groupByName($this->getExpenses($accounts, $all, $currentStart, $currentEnd));
            $income   = $this->groupByName($this->getIncome($accounts, $all, $currentStart, $currentEnd));
            $label    = $currentStart->formatLocalized($format);

            foreach ($combined as $name => $combi) {
                // first is always expense account:
                /** @var Account $exp */
                $exp            = $combi->first();
                $labelIn        = $exp->id . '-in';
                $labelOut       = $exp->id . '-out';
                $labelSumIn     = $exp->id . '-total-in';
                $labelSumOut    = $exp->id . '-total-out';
                $currentIncome  = $income[$name] ?? '0';
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
            $currentStart = clone $currentEnd;
            $currentStart->addDay();
        }
        // remove all empty entries to prevent cluttering:
        $newSet = [];
        foreach ($chartData as $key => $entry) {
            if (0 === !array_sum($entry['entries'])) {
                $newSet[$key] = $chartData[$key];
            }
        }
        if (0 === count($newSet)) {
            $newSet = $chartData; // @codeCoverageIgnore
        }
        $data = $this->generator->multiSet($newSet);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param Collection $accounts
     *
     * @return array
     */
    protected function combineAccounts(Collection $accounts): array
    {
        $combined = [];
        /** @var Account $expenseAccount */
        foreach ($accounts as $expenseAccount) {
            $collection = new Collection;
            $collection->push($expenseAccount);

            $revenue = $this->accountRepository->findByName($expenseAccount->name, [AccountType::REVENUE]);
            if (!is_null($revenue)) {
                $collection->push($revenue);
            }
            $combined[$expenseAccount->name] = $collection;
        }

        return $combined;
    }

    /**
     * @param Collection $accounts
     * @param Collection $opposing
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    private function getExpenses(Collection $accounts, Collection $opposing, Carbon $start, Carbon $end): Collection
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setOpposingAccounts($opposing);

        $transactions = $collector->getJournals();

        return $transactions;
    }

    /**
     * @param Collection $accounts
     * @param Collection $opposing
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    private function getIncome(Collection $accounts, Collection $opposing, Carbon $start, Carbon $end): Collection
    {
        /** @var JournalCollectorInterface $collector */
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->setOpposingAccounts($opposing);

        $transactions = $collector->getJournals();

        return $transactions;
    }

    /**
     * @param Collection $set
     *
     * @return array
     */
    private function groupByName(Collection $set): array
    {
        // group by opposing account name.
        $grouped = [];
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $name           = $transaction->opposing_account_name;
            $grouped[$name] = $grouped[$name] ?? '0';
            $grouped[$name] = bcadd($transaction->transaction_amount, $grouped[$name]);
        }

        return $grouped;
    }
}
