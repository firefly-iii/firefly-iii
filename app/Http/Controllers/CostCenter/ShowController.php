<?php
/**
 * ShowController.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\CostCenter;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\CostCenter;
use FireflyIII\Repositories\CostCenter\CostCenterRepositoryInterface;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Log;

/**
 *
 * Class ShowController
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShowController extends Controller
{
    use PeriodOverview;
    /** @var CostCenterRepositoryInterface The cost center repository */
    private $repository;

    /**
     * CostCenterController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.cost_centers'));
                app('view')->share('mainTitleIcon', 'fa-bar-chart');
                $this->repository = app(CostCenterRepositoryInterface::class);

                return $next($request);
            }
        );
    }


    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Show a single costCenter.
     *
     * @param Request     $request
     * @param CostCenter  $costCenter
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Request $request, CostCenter $costCenter, Carbon $start = null, Carbon $end = null)
    {
        Log::debug('Now in show()');
        /** @var Carbon $start */
        $start = $start ?? session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end          = $end ?? session('end', Carbon::now()->endOfMonth());
        $subTitleIcon = 'fa-bar-chart';
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $periods      = $this->getNoCostCenterPeriodOverview($costCenter, $end);
        $path         = route('cost-center.show', [$costCenter->id, $start->format('Y-m-d'), $end->format('Y-m-d')]);
        $subTitle     = trans(
            'firefly.journals_in_period_for_cost_center',
            ['name' => $costCenter->name, 'start' => $start->formatLocalized($this->monthAndDayFormat),
             'end'  => $end->formatLocalized($this->monthAndDayFormat),]
        );

        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withOpposingAccount()
                  ->setCostCenter($costCenter)->withBudgetInformation()->withCostCenterInformation();
        $collector->removeFilter(InternalTransferFilter::class);
        $transactions = $collector->getPaginatedTransactions();
        $transactions->setPath($path);

        Log::debug('End of show()');

        return view('cost-center.show', compact('costCenter', 'transactions', 'periods', 'subTitle', 'subTitleIcon', 'start', 'end'));
    }

    /**
     * Show all transactions within a cost center.
     *
     * @param Request  $request
     * @param CostCenter $costCenter
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showAll(Request $request, CostCenter $costCenter)
    {
        // default values:
        $subTitleIcon = 'fa-bar-chart';
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $start        = null;
        $end          = null;
        $periods      = new Collection;

        $subTitle = (string)trans('firefly.all_journals_for_cost_center', ['name' => $costCenter->name]);
        $first    = $this->repository->firstUseDate($costCenter);
        /** @var Carbon $start */
        $start = $first ?? new Carbon;
        $end   = new Carbon;
        $path  = route('cost-center.show.all', [$costCenter->id]);


        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withOpposingAccount()
                  ->setCostCenter($costCenter)->withBudgetInformation()->withCostCenterInformation();
        $collector->removeFilter(InternalTransferFilter::class);
        $transactions = $collector->getPaginatedTransactions();
        $transactions->setPath($path);

        return view('cost-center.show', compact('costCenter', 'transactions', 'periods', 'subTitle', 'subTitleIcon', 'start', 'end'));
    }
}
