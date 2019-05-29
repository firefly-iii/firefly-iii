<?php
/**
 * IndexController.php
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

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Transaction;


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Support\Http\Controllers\ModelInformation;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Http\Request;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    use  PeriodOverview;
    /**
     * Index for a range of transactions.
     *
     * @param Request     $request
     * @param string      $transactionType
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request, string $transactionType, Carbon $start = null, Carbon $end = null)
    {
        $subTitleIcon = config('firefly.transactionIconsByType.' . $transactionType);
        $types        = config('firefly.transactionTypesByType.' . $transactionType);
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        if (null === $start) {
            $start = session('start');
            $end   = session('end');
        }
        if (null === $end) {
            $end = session('end');
        }

        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }

        $path = route('transactions.index', [$transactionType, $start->format('Y-m-d'), $end->format('Y-m-d')]);

        $startStr = $start->formatLocalized($this->monthAndDayFormat);
        $endStr   = $end->formatLocalized($this->monthAndDayFormat);
        $subTitle = (string)trans(sprintf('firefly.title_%s_between',$transactionType), ['start' => $startStr, 'end' => $endStr]);

        $periods  = $this->getTransactionPeriodOverview($transactionType, $end);

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setRange($start, $end)
                  ->setTypes($types)
                  ->setLimit($pageSize)
                  ->setPage($page)
                  ->withBudgetInformation()
                  ->withCategoryInformation()
                  ->withAccountInformation();
        $groups = $collector->getPaginatedGroups();
        $groups->setPath($path);

        return view('transactions.index', compact('subTitle', 'transactionType', 'subTitleIcon', 'groups', 'periods', 'start', 'end'));
    }
}