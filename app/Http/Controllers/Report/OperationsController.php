<?php
/**
 * OperationsController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Report;


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;

/**
 * Class OperationsController
 *
 * @package FireflyIII\Http\Controllers\Report
 */
class OperationsController extends Controller
{

    /**
     * @param AccountTaskerInterface $tasker
     * @param Collection             $accounts
     * @param Carbon                 $start
     * @param Carbon                 $end
     *
     * @return mixed|string
     */
    public function expenses(AccountTaskerInterface $tasker, Collection $accounts, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('expense-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $entries = $tasker->getExpenseReport($start, $end, $accounts);
        $type    = 'expense-entry';
        $result  = view('reports.partials.income-expenses', compact('entries', 'type'))->render();
        $cache->store($result);

        return $result;

    }

    /**
     * @param AccountTaskerInterface $tasker
     * @param Collection             $accounts
     * @param Carbon                 $start
     * @param Carbon                 $end
     *
     * @return string
     */
    public function income(AccountTaskerInterface $tasker, Collection $accounts, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('income-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $entries = $tasker->getIncomeReport($start, $end, $accounts);
        $type    = 'income-entry';
        $result  = view('reports.partials.income-expenses', compact('entries', 'type'))->render();

        $cache->store($result);

        return $result;

    }

    /**
     * @param AccountTaskerInterface $tasker
     * @param Collection             $accounts
     * @param Carbon                 $start
     * @param Carbon                 $end
     *
     * @return mixed|string
     */
    public function operations(AccountTaskerInterface $tasker, Collection $accounts, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('inc-exp-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $incomes   = $tasker->getIncomeReport($start, $end, $accounts);
        $expenses  = $tasker->getExpenseReport($start, $end, $accounts);
        $incomeSum = array_sum(
            array_map(
                function ($item) {
                    return $item['sum'];
                }, $incomes
            )
        );

        $expensesSum = array_sum(
            array_map(
                function ($item) {
                    return $item['sum'];
                }, $expenses
            )
        );

        $result = view('reports.partials.operations', compact('incomeSum', 'expensesSum'))->render();
        $cache->store($result);

        return $result;

    }

}
