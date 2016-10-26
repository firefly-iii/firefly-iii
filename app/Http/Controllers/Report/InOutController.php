<?php
/**
 * InOutController.php
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
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Response;

/**
 * Class InOutController
 *
 * @package FireflyIII\Http\Controllers\Report
 */
class InOutController extends Controller
{

    /**
     * @param ReportHelperInterface $helper
     * @param Carbon                $start
     * @param Carbon                $end
     * @param Collection            $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function inOutReport(ReportHelperInterface $helper, Carbon $start, Carbon $end, Collection $accounts)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('in-out-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $incomes  = $helper->getIncomeReport($start, $end, $accounts);
        $expenses = $helper->getExpenseReport($start, $end, $accounts);

        $result = [
            'income'           => view('reports.partials.income', compact('incomes'))->render(),
            'expenses'         => view('reports.partials.expenses', compact('expenses'))->render(),
            'incomes_expenses' => view('reports.partials.income-vs-expenses', compact('expenses', 'incomes'))->render(),
        ];
        $cache->store($result);

        return Response::json($result);

    }

}