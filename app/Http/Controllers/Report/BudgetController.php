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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Report\BudgetReportHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Navigation;

/**
 * Class BudgetController
 *
 * @package FireflyIII\Http\Controllers\Report
 */
class BudgetController extends Controller
{


    /**
     * @param BudgetReportHelperInterface $helper
     * @param Collection                  $accounts
     * @param Carbon                      $start
     * @param Carbon                      $end
     *
     * @return mixed|string
     */
    public function general(BudgetReportHelperInterface $helper, Collection $accounts, Carbon $start, Carbon $end)
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

        $budgets = $helper->getBudgetReport($start, $end, $accounts);

        $result = view('reports.partials.budgets', compact('budgets'))->render();
        $cache->store($result);

        return $result;
    }

    /**
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
            return $cache->get(); // @codeCoverageIgnore
        }

        // generate budget report right here.
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $budgets    = $repository->getBudgets();
        $data       = $repository->getBudgetPeriodReport($budgets, $accounts, $start, $end);
        $data[0]    = $repository->getNoBudgetPeriodReport($accounts, $start, $end); // append report data for "no budget"
        $report     = $this->filterBudgetPeriodReport($data);
        $periods    = Navigation::listOfPeriods($start, $end);

        $result = view('reports.partials.budget-period', compact('report', 'periods'))->render();
        $cache->store($result);

        return $result;
    }

    /**
     * Filters empty results from getBudgetPeriodReport
     *
     * @param array $data
     *
     * @return array
     */
    private function filterBudgetPeriodReport(array $data): array
    {
        /**
         * @var int   $budgetId
         * @var array $set
         */
        foreach ($data as $budgetId => $set) {
            $sum = '0';
            foreach ($set['entries'] as $amount) {
                $sum = bcadd($amount, $sum);
            }
            $data[$budgetId]['sum'] = $sum;
            if (bccomp('0', $sum) === 0) {
                unset($data[$budgetId]);
            }
        }

        return $data;
    }
}
