<?php
/**
 * OperationsController.php
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
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;

/**
 * Class OperationsController.
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
                },
                $incomes
            )
        );

        $expensesSum = array_sum(
            array_map(
                function ($item) {
                    return $item['sum'];
                },
                $expenses
            )
        );

        $result = view('reports.partials.operations', compact('incomeSum', 'expensesSum'))->render();
        $cache->store($result);

        return $result;
    }
}
