<?php

/**
 * IndexController.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Transaction;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    use PeriodOverview;

    private JournalRepositoryInterface $repository;

    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('showCategory', true);
        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-exchange');
                app('view')->share('title', (string) trans('firefly.transactions'));

                $this->repository = app(JournalRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Index for a range of transactions.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function index(Request $request, string $objectType, ?Carbon $start = null, ?Carbon $end = null)
    {
        if ('transfers' === $objectType) {
            $objectType = 'transfer';
        }

        $subTitleIcon  = config('firefly.transactionIconsByType.'.$objectType);
        $types         = config('firefly.transactionTypesByType.'.$objectType);
        $page          = (int) $request->get('page');
        $pageSize      = (int) app('preferences')->get('listPageSize', 50)->data;

        if (null === $start) {
            $start = session('start');
            $end   = session('end');
        }
        if (null === $end) {
            // get last transaction ever?
            $last = $this->repository->getLast();
            $end  = null !== $last ? $last->date : session('end');
        }

        [$start, $end] = $end < $start ? [$end, $start] : [$start, $end];
        $startStr      = $start->isoFormat($this->monthAndDayFormat);
        $endStr        = $end->isoFormat($this->monthAndDayFormat);
        $subTitle      = (string) trans(sprintf('firefly.title_%s_between', $objectType), ['start' => $startStr, 'end' => $endStr]);
        $path          = route('transactions.index', [$objectType, $start->format('Y-m-d'), $end->format('Y-m-d')]);
        $firstJournal  = $this->repository->firstNull();
        $startPeriod   = null === $firstJournal ? new Carbon() : $firstJournal->date;
        $endPeriod     = clone $end;
        $periods       = $this->getTransactionPeriodOverview($objectType, $startPeriod, $endPeriod);

        /** @var GroupCollectorInterface $collector */
        $collector     = app(GroupCollectorInterface::class);

        $collector->setRange($start, $end)
            ->setTypes($types)
            ->setLimit($pageSize)
            ->setPage($page)
            ->withBudgetInformation()
            ->withCategoryInformation()
            ->withAccountInformation()
            ->withAttachmentInformation()
        ;
        $groups        = $collector->getPaginatedGroups();
        $groups->setPath($path);

        return view('transactions.index', compact('subTitle', 'objectType', 'subTitleIcon', 'groups', 'periods', 'start', 'end'));
    }

    /**
     * Index for ALL transactions.
     *
     * @return Factory|View
     */
    public function indexAll(Request $request, string $objectType)
    {
        $subTitleIcon = config('firefly.transactionIconsByType.'.$objectType);
        $types        = config('firefly.transactionTypesByType.'.$objectType);
        $page         = (int) $request->get('page');
        $pageSize     = (int) app('preferences')->get('listPageSize', 50)->data;
        $path         = route('transactions.index.all', [$objectType]);
        $first        = $this->repository->firstNull();
        $start        = null === $first ? new Carbon() : $first->date;
        $last         = $this->repository->getLast();
        $end          = null !== $last ? $last->date : today(config('app.timezone'));
        $subTitle     = (string) trans('firefly.all_'.$objectType);

        /** @var GroupCollectorInterface $collector */
        $collector    = app(GroupCollectorInterface::class);

        $collector->setRange($start, $end)
            ->setTypes($types)
            ->setLimit($pageSize)
            ->setPage($page)
            ->withAccountInformation()
            ->withBudgetInformation()
            ->withCategoryInformation()
            ->withAttachmentInformation()
        ;
        $groups       = $collector->getPaginatedGroups();
        $groups->setPath($path);

        return view('transactions.index', compact('subTitle', 'objectType', 'subTitleIcon', 'groups', 'start', 'end'));
    }
}
