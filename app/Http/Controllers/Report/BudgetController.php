<?php
/**
 * BudgetController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Report;


use Carbon\Carbon;
use FireflyIII\Helpers\Report\BudgetReportHelperInterface;
use FireflyIII\Http\Controllers\Controller;
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
     *
     * @param BudgetReportHelperInterface $helper
     * @param Carbon                      $start
     * @param Carbon                      $end
     * @param Collection                  $accounts
     *
     * @return mixed|string
     */
    public function budgetPeriodReport(BudgetReportHelperInterface $helper, Carbon $start, Carbon $end, Collection $accounts)
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('budget-period-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get();
        }

        $periods = Navigation::listOfPeriods($start, $end);
        $budgets = $helper->getBudgetPeriodReport($start, $end, $accounts);
        $result  = view('reports.partials.budget-period', compact('budgets', 'periods'))->render();
        $cache->store($result);

        return $result;
    }

    /**
     * @param BudgetReportHelperInterface $helper
     * @param Carbon                      $start
     * @param Carbon                      $end
     * @param Collection                  $accounts
     *
     * @return string
     */
    public function budgetReport(BudgetReportHelperInterface $helper, Carbon $start, Carbon $end, Collection $accounts)
    {

        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('budget-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get();
        }

        $budgets = $helper->getBudgetReport($start, $end, $accounts);

        $result = view('reports.partials.budgets', compact('budgets'))->render();
        $cache->store($result);

        return $result;

    }
}