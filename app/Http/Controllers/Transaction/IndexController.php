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
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Http\Request;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    use PeriodOverview;

    /** @var JournalRepositoryInterface */
    private $repository;

    /**
     * IndexController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-credit-card');
                app('view')->share('title', (string)trans('firefly.accounts'));

                $this->repository = app(JournalRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Index for a range of transactions.
     *
     * @param Request $request
     * @param string $objectType
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function index(Request $request, string $objectType, Carbon $start = null, Carbon $end = null)
    {
        $subTitleIcon = config('firefly.transactionIconsByType.' . $objectType);
        $types        = config('firefly.transactionTypesByType.' . $objectType);
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        if (null === $start) {
            $start = session('start');
            $end   = session('end');
        }
        if (null === $end) {
            $end = session('end'); // @codeCoverageIgnore
        }

        [$start, $end] = $end < $start ? [$end, $start] : [$start, $end];
        $path     = route('transactions.index', [$objectType, $start->format('Y-m-d'), $end->format('Y-m-d')]);
        $startStr = $start->formatLocalized($this->monthAndDayFormat);
        $endStr   = $end->formatLocalized($this->monthAndDayFormat);
        $subTitle = (string)trans(sprintf('firefly.title_%s_between', $objectType), ['start' => $startStr, 'end' => $endStr]);

        $firstJournal = $this->repository->firstNull();
        $startPeriod  = null === $firstJournal ? new Carbon : $firstJournal->date;
        $endPeriod    = clone $end;
        $periods      = $this->getTransactionPeriodOverview($objectType, $startPeriod, $endPeriod);

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

        return view('transactions.index', compact('subTitle', 'objectType', 'subTitleIcon', 'groups', 'periods', 'start', 'end'));
    }

    /**
     * Index for ALL transactions.
     *
     * @param Request $request
     * @param string $objectType
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function indexAll(Request $request, string $objectType)
    {
        /** @var JournalRepositoryInterface $repository */
        $repository = app(JournalRepositoryInterface::class);


        $subTitleIcon = config('firefly.transactionIconsByWhat.' . $objectType);
        $types        = config('firefly.transactionTypesByWhat.' . $objectType);
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $path         = route('transactions.index.all', [$objectType]);
        $first        = $repository->firstNull();
        $start        = null === $first ? new Carbon : $first->date;
        $end          = new Carbon;
        $subTitle     = (string)trans('firefly.all_' . $objectType);

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setRange($start, $end)
                  ->setTypes($types)
                  ->setLimit($pageSize)
                  ->setPage($page)
                  ->withAccountInformation()
                  ->withBudgetInformation()
                  ->withCategoryInformation();
        $groups = $collector->getPaginatedGroups();
        $groups->setPath($path);

        return view('transactions.index', compact('subTitle', 'objectType', 'subTitleIcon', 'groups', 'start', 'end'));
    }
}