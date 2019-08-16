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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Report;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Log;
use Throwable;

/**
 * Class OperationsController.
 */
class OperationsController extends Controller
{

    /** @var AccountTaskerInterface Some specific account things. */
    private $tasker;

    /**
     * OperationsController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                $this->tasker = app(AccountTaskerInterface::class);

                return $next($request);
            }
        );
    }


    /**
     * View of income and expense.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return mixed|string
     */
    public function expenses(Collection $accounts, Carbon $start, Carbon $end)
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
        $report = $this->tasker->getExpenseReport($start, $end, $accounts);
        $type   = 'expense-entry';
        try {
            $result = view('reports.partials.income-expenses', compact('report', 'type'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render reports.partials.income-expense: %s', $e->getMessage()));
            $result = 'Could not render view.';
        }
        // @codeCoverageIgnoreEnd
        $cache->store($result);

        return $result;
    }

    /**
     * View of income.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function income(Collection $accounts, Carbon $start, Carbon $end): string
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
        $report = $this->tasker->getIncomeReport($start, $end, $accounts);
        $type   = 'income-entry';
        try {
            $result = view('reports.partials.income-expenses', compact('report', 'type'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render reports.partials.income-expenses: %s', $e->getMessage()));
            $result = 'Could not render view.';
        }
        // @codeCoverageIgnoreEnd
        $cache->store($result);

        return $result;
    }

    /**
     * Overview of income and expense.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return mixed|string
     */
    public function operations(Collection $accounts, Carbon $start, Carbon $end)
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

        $incomes  = $this->tasker->getIncomeReport($start, $end, $accounts);
        $expenses = $this->tasker->getExpenseReport($start, $end, $accounts);
        $sums     = [];
        $keys     = array_unique(array_merge(array_keys($incomes['sums']), array_keys($expenses['sums'])));

        /** @var int $currencyId */
        foreach ($keys as $currencyId) {
            $currencyInfo             = $incomes['sums'][$currencyId] ?? $expenses['sums'][$currencyId];
            $sums[$currencyId]        = $sums[$currencyId] ?? [
                    'currency_id'             => $currencyId,
                    'currency_name'           => $currencyInfo['currency_name'],
                    'currency_code'           => $currencyInfo['currency_code'],
                    'currency_symbol'         => $currencyInfo['currency_symbol'],
                    'currency_decimal_places' => $currencyInfo['currency_decimal_places'],
                    'in'                      => $incomes['sums'][$currencyId]['sum'] ?? '0',
                    'out'                     => $expenses['sums'][$currencyId]['sum'] ?? '0',
                    'sum'                     => '0',
                ];
            $sums[$currencyId]['sum'] = bcadd($sums[$currencyId]['in'], $sums[$currencyId]['out']);
        }

        //try {
            $result = view('reports.partials.operations', compact('sums'))->render();
            // @codeCoverageIgnoreStart
//        } catch (Throwable $e) {
//            Log::debug(sprintf('Could not render reports.partials.operations: %s', $e->getMessage()));
//            $result = 'Could not render view.';
//        }
        // @codeCoverageIgnoreEnd
        $cache->store($result);

        return $result;
    }
}
