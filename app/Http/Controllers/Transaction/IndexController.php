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
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

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
                app('view')->share('title', (string)trans('firefly.transactions'));

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
     * @throws ContainerExceptionInterface
     * @throws FireflyException
     * @throws NotFoundExceptionInterface
     */
    public function index(Request $request, string $objectType, ?Carbon $start = null, ?Carbon $end = null): Factory | \Illuminate\Contracts\View\View
    {
        if ('transfers' === $objectType) {
            $objectType = 'transfer';
        }

        $subTitleIcon = config('firefly.transactionIconsByType.' . $objectType);
        $types        = config('firefly.transactionTypesByType.' . $objectType);
        $page         = (int)$request->get('page');
        $pageSize     = (int)Preferences::get('listPageSize', 50)->data;

        if (!$start instanceof Carbon) {
            $start = session('start');
            $end   = session('end');
        }
        if (null === $end) {
            // get last transaction ever?
            $last = $this->repository->getLast();
            $end  = $last instanceof TransactionJournal ? $last->date : session('end');
        }

        [$start, $end] = $end < $start ? [$end, $start] : [$start, $end];
        $startStr     = $start->isoFormat($this->monthAndDayFormat);
        $endStr       = $end->isoFormat($this->monthAndDayFormat);
        $subTitle     = (string)trans(sprintf('firefly.title_%s_between', $objectType), ['start' => $startStr, 'end' => $endStr]);
        $path         = route('transactions.index', [$objectType, $start->format('Y-m-d'), $end->format('Y-m-d')]);
        $firstJournal = $this->repository->firstNull();
        $startPeriod  = $firstJournal instanceof TransactionJournal ? $firstJournal->date : new Carbon();
        $endPeriod    = clone $end;

        // limit to 3 years for the time being.
        if (now()->diffInYears($startPeriod, true) > 3) {
            $startPeriod = now()->subYears(3);
        }

        $periods = $this->getTransactionPeriodOverview($objectType, $startPeriod, $endPeriod);

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setRange($start, $end)
                  ->setTypes($types)
                  ->setLimit($pageSize)
                  ->setPage($page)
                  ->withBudgetInformation()
                  ->withCategoryInformation()
                  ->withAccountInformation()
                  ->withAttachmentInformation();
        $groups = $collector->getPaginatedGroups();
        $groups->setPath($path);

        return view('transactions.index', ['subTitle' => $subTitle, 'objectType' => $objectType, 'subTitleIcon' => $subTitleIcon, 'groups' => $groups, 'periods' => $periods, 'start' => $start, 'end' => $end]);
    }

    /**
     * Index for ALL transactions.
     *
     * @return Factory|View
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function indexAll(Request $request, string $objectType): Factory | \Illuminate\Contracts\View\View
    {
        $subTitleIcon = config('firefly.transactionIconsByType.' . $objectType);
        $types        = config('firefly.transactionTypesByType.' . $objectType);
        $page         = (int)$request->get('page');
        $pageSize     = (int)Preferences::get('listPageSize', 50)->data;
        $path         = route('transactions.index.all', [$objectType]);
        $first        = $this->repository->firstNull();
        $start        = $first instanceof TransactionJournal ? $first->date : new Carbon();
        $last         = $this->repository->getLast();
        $end          = $last instanceof TransactionJournal ? $last->date : today(config('app.timezone'));
        $subTitle     = (string)trans('firefly.all_' . $objectType);

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setRange($start, $end)
                  ->setTypes($types)
                  ->setLimit($pageSize)
                  ->setPage($page)
                  ->withAccountInformation()
                  ->withBudgetInformation()
                  ->withCategoryInformation()
                  ->withAttachmentInformation();
        $groups = $collector->getPaginatedGroups();
        $groups->setPath($path);

        return view('transactions.index', ['subTitle' => $subTitle, 'objectType' => $objectType, 'subTitleIcon' => $subTitleIcon, 'groups' => $groups, 'start' => $start, 'end' => $end]);
    }
}
