<?php
/**
 * BillController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\Report;


use Carbon\Carbon;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Log;
use Throwable;

/**
 * Class BillController
 */
class BillController extends Controller
{
    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     */
    public function overview(Collection $accounts, Carbon $start, Carbon $end)
    {   // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('bill-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            //return $cache->get(); // @codeCoverageIgnore
        }


        /** @var ReportHelperInterface $helper */
        $helper = app(ReportHelperInterface::class);
        $report = $helper->getBillReport($accounts, $start, $end);


//        try {
            $result = view('reports.partials.bills', compact('report'))->render();
            // @codeCoverageIgnoreStart
//        } catch (Throwable $e) {
//            Log::debug(sprintf('Could not render reports.partials.budgets: %s', $e->getMessage()));
//            $result = 'Could not render view.';
//        }
        // @codeCoverageIgnoreEnd
        $cache->store($result);

        return $result;

    }
}