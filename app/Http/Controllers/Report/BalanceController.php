<?php
/**
 * BalanceController.php
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
use FireflyIII\Helpers\Report\BalanceReportHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Log;
use Throwable;

/**
 * Class BalanceController.
 */
class BalanceController extends Controller
{

    /**
     * Show overview of budget balances.
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
        $cache->addProperty('balance-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            //return $cache->get(); // @codeCoverageIgnore
        }
        $helper  = app(BalanceReportHelperInterface::class);
        $report = $helper->getBalanceReport($accounts, $start, $end);
        // TODO no budget.
        // TODO sum over account.
//        try {
            $result = view('reports.partials.balance', compact('report'))->render();
            // @codeCoverageIgnoreStart
//        } catch (Throwable $e) {
//            Log::debug(sprintf('Could not render reports.partials.balance: %s', $e->getMessage()));
//            $result = 'Could not render view.';
//        }
        // @codeCoverageIgnoreEnd
        $cache->store($result);

        return $result;
    }
}
