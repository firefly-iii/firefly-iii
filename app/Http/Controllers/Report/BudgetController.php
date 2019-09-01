<?php
/**
 * BudgetController.php
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

namespace FireflyIII\Http\Controllers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Report\BudgetReportHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\NoBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\BasicDataSupport;
use Illuminate\Support\Collection;
use Log;
use Throwable;

/**
 * Class BudgetController.
 */
class BudgetController extends Controller
{
    use BasicDataSupport;

    /**
     * Show partial overview of budgets.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return mixed|string
     */
    public function general(Collection $accounts, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('budget-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $helper  = app(BudgetReportHelperInterface::class);
        $budgets = $helper->getBudgetReport($start, $end, $accounts);
        try {
            $result = view('reports.partials.budgets', compact('budgets'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render reports.partials.budgets: %s', $e->getMessage()));
            $result = 'Could not render view.';
        }
        // @codeCoverageIgnoreEnd
        $cache->store($result);

        return $result;
    }


    /**
     * Show budget overview for a period.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return mixed|string
     */
    public function period(Collection $accounts, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('budget-period-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            // return $cache->get(); // @codeCoverageIgnore
        }

        // generate budget report right here.
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);

        /** @var OperationsRepositoryInterface $opsRepository */
        $opsRepository = app(OperationsRepositoryInterface::class);

        /** @var NoBudgetRepositoryInterface $nbRepository */
        $nbRepository = app(NoBudgetRepositoryInterface::class);


        $budgets   = $repository->getBudgets();
        $periods   = app('navigation')->listOfPeriods($start, $end);
        $keyFormat = app('navigation')->preferredCarbonFormat($start, $end);



        // list expenses for budgets in account(s)
        $expenses = $opsRepository->listExpenses($start, $end, $accounts);

        $report   = [];
        foreach ($expenses as $currency) {
            foreach ($currency['budgets'] as $budget) {
                foreach ($budget['transaction_journals'] as $journal) {
                    $key                                = sprintf('%d-%d', $budget['id'], $currency['currency_id']);
                    $dateKey                            = $journal['date']->format($keyFormat);
                    $report[$key]                       = $report[$key] ?? [
                            'id'                      => $budget['id'],
                            'name'                    => sprintf('%s (%s)', $budget['name'], $currency['currency_name']),
                            'sum'                     => '0',
                            'currency_id'             => $currency['currency_id'],
                            'currency_name'           => $currency['currency_name'],
                            'currency_symbol'         => $currency['currency_symbol'],
                            'currency_code'           => $currency['currency_code'],
                            'currency_decimal_places' => $currency['currency_decimal_places'],
                            'entries'                 => [],
                        ];
                    $report[$key] ['entries'][$dateKey] = $report[$key] ['entries'][$dateKey] ?? '0';
                    $report[$key] ['entries'][$dateKey] = bcadd($journal['amount'], $report[$key] ['entries'][$dateKey]);
                    $report[$key] ['sum'] = bcadd($report[$key] ['sum'], $journal['amount']);
                }
            }
        }
        try {
            $result = view('reports.partials.budget-period', compact('report', 'periods'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = 'Could not render view.';
        }
        // @codeCoverageIgnoreEnd
        $cache->store($result);

        return $result;
    }

}
