<?php
/**
 * IndexController.php
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

namespace FireflyIII\Http\Controllers\Budget;


use Carbon\Carbon;
use Exception;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Log;

/**
 *
 * Class IndexController
 */
class IndexController extends Controller
{

    use DateCalculation;
    /** @var BudgetRepositoryInterface The budget repository */
    private $repository;

    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        app('view')->share('hideBudgets', true);

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.budgets'));
                app('view')->share('mainTitleIcon', 'fa-tasks');
                $this->repository = app(BudgetRepositoryInterface::class);

                return $next($request);
            }
        );
    }


    /**
     * Show all budgets.
     *
     * TODO remove moment routine.
     *
     * @param Request     $request
     * @param string|null $moment
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function index(Request $request, string $moment = null)
    {
        $range    = app('preferences')->get('viewRange', '1M')->data;
        /** @var Carbon $start */
        $start    = session('start', new Carbon);
        /** @var Carbon $end */
        $end      = session('end', new Carbon);
        $page     = 0 === (int)$request->get('page') ? 1 : (int)$request->get('page');
        $pageSize = (int)app('preferences')->get('listPageSize', 50)->data;
        $moment   = $moment ?? '';

        // make date if the data is given.
        if ('' !== (string)$moment) {
            try {
                $start = new Carbon($moment);
                /** @var Carbon $end */
                $end   = app('navigation')->endOfPeriod($start, $range);
            } catch (Exception $e) {
                // start and end are already defined.
                Log::debug(sprintf('start and end are already defined: %s', $e->getMessage()));
            }
        }

        // if today is between start and end, use the diff in days between end and today (days left)
        // otherwise, use diff between start and end.
        $dayDifference = $this->getDayDifference($start, $end);

        $next = clone $end;
        $next->addDay();
        $prev = clone $start;
        $prev->subDay();
        $prev = app('navigation')->startOfPeriod($prev, $range);
        $this->repository->cleanupBudgets();
        $daysPassed        = $this->getDaysPassedInPeriod($start, $end);
        $allBudgets        = $this->repository->getActiveBudgets();
        $total             = $allBudgets->count();
        $budgets           = $allBudgets->slice(($page - 1) * $pageSize, $pageSize);
        $inactive          = $this->repository->getInactiveBudgets();
        $periodStart       = $start->formatLocalized($this->monthAndDayFormat);
        $periodEnd         = $end->formatLocalized($this->monthAndDayFormat);
        $budgetInformation = $this->repository->collectBudgetInformation($allBudgets, $start, $end);
        $defaultCurrency   = app('amount')->getDefaultCurrency();
        $available         = $this->repository->getAvailableBudget($defaultCurrency, $start, $end);
        $spent             = array_sum(array_column($budgetInformation, 'spent'));
        $budgeted          = array_sum(array_column($budgetInformation, 'budgeted'));
        $previousLoop      = $this->getPreviousPeriods($start, $range);
        $nextLoop          = $this->getNextPeriods($end, $range);

        // paginate budgets
        $budgets = new LengthAwarePaginator($budgets, $total, $pageSize, $page);
        $budgets->setPath(route('budgets.index'));
        // display info
        $currentMonth = app('navigation')->periodShow($start, $range);
        $nextText     = app('navigation')->periodShow($next, $range);
        $prevText     = app('navigation')->periodShow($prev, $range);

        return view(
            'budgets.index', compact(
                               'available', 'currentMonth', 'next', 'nextText', 'prev', 'allBudgets', 'prevText', 'periodStart', 'periodEnd', 'dayDifference',
                               'page',
                               'budgetInformation', 'daysPassed',
                               'inactive', 'budgets', 'spent', 'budgeted', 'previousLoop', 'nextLoop', 'start', 'end'
                           )
        );
    }


}