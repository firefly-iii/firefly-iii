<?php
/**
 * CostCenterController.php
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
use FireflyIII\Models\CostCenter;
use FireflyIII\Repositories\CostCenter\CostCenterRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\BasicDataSupport;
use Illuminate\Support\Collection;
use Log;
use Throwable;

/**
 * Class CostCenterController.
 */
class CostCenterController extends Controller
{
    use BasicDataSupport;

    /**
     * Show overview of expenses in cost center.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return mixed|string
     */
    public function expenses(Collection $accounts, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('cost-center-period-expenses-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        /** @var CostCenterRepositoryInterface $repository */
        $repository  = app(CostCenterRepositoryInterface::class);
        $costCenters = $repository->getCostCenters();
        $data        = $repository->periodExpenses($costCenters, $accounts, $start, $end);
        $data[0]     = $repository->periodExpensesNoCostCenter($accounts, $start, $end);
        $report      = $this->filterPeriodReport($data);
        $periods     = app('navigation')->listOfPeriods($start, $end);
        try {
            $result = view('reports.partials.cost-center-period', compact('report', 'periods'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render cost-center::expenses: %s', $e->getMessage()));
            $result = 'An error prevented Firefly III from rendering. Apologies.';
        }
        // @codeCoverageIgnoreEnd

        $cache->store($result);

        return $result;
    }


    /**
     * Show overview of income in cost center.
     *
     * @param Collection $accounts
     *
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function income(Collection $accounts, Carbon $start, Carbon $end): string
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('cost-center-period-income-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        /** @var CostCenterRepositoryInterface $repository */
        $repository = app(CostCenterRepositoryInterface::class);
        $costCenters = $repository->getCostCenters();
        $data       = $repository->periodIncome($costCenters, $accounts, $start, $end);
        $data[0]    = $repository->periodIncomeNoCostCenter($accounts, $start, $end);
        $report     = $this->filterPeriodReport($data);
        $periods    = app('navigation')->listOfPeriods($start, $end);
        try {
            $result = view('reports.partials.cost-center-period', compact('report', 'periods'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render cost center::expenses: %s', $e->getMessage()));
            $result = 'An error prevented Firefly III from rendering. Apologies.';
        }
        // @codeCoverageIgnoreEnd
        $cache->store($result);

        return $result;
    }


    /**
     * Show overview of operations.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return mixed|string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function operations(Collection $accounts, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('cost-center-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        /** @var CostCenterRepositoryInterface $repository */
        $repository = app(CostCenterRepositoryInterface::class);
        $costCenters = $repository->getCostCenters();
        $report     = [];
        /** @var CostCenter $costCenter */
        foreach ($costCenters as $costCenter) {
            $spent  = $repository->spentInPeriod(new Collection([$costCenter]), $accounts, $start, $end);
            $earned = $repository->earnedInPeriod(new Collection([$costCenter]), $accounts, $start, $end);
            if (0 !== bccomp($spent, '0') || 0 !== bccomp($earned, '0')) {
                $report[$costCenter->id] = [
                    'name' => $costCenter->name, 
                    'spent' => $spent, 
                    'earned' => $earned, 
                    'id' => $costCenter->id
                ];
            }
        }
        $sum = [];
        foreach ($report as $costCenterId => $row) {
            $sum[$costCenterId] = (float)$row['spent'];
        }
        array_multisort($sum, SORT_ASC, $report);
        try {
            $result = view('reports.partials.cost-centers', compact('report'))->render();
            $cache->store($result);
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render cost center::expenses: %s', $e->getMessage()));
            $result = 'An error prevented Firefly III from rendering. Apologies.';
        }

        // @codeCoverageIgnoreEnd

        return $result;
    }


}
