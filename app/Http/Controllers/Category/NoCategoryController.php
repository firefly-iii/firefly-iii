<?php

/**
 * NoCategoryController.php
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

namespace FireflyIII\Http\Controllers\Category;

use Carbon\Carbon;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class NoCategoryController
 */
class NoCategoryController extends Controller
{
    use PeriodOverview;

    protected JournalRepositoryInterface $journalRepos;

    /**
     * CategoryController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('showBudget', true);

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.categories'));
                app('view')->share('mainTitleIcon', 'fa-bookmark');
                $this->journalRepos = app(JournalRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Show transactions without a category.
     *
     * @return Factory|View
     *
     * @throws ContainerExceptionInterface
     * @throws FireflyException
     * @throws NotFoundExceptionInterface
     */
    public function show(Request $request, ?Carbon $start = null, ?Carbon $end = null): Factory|\Illuminate\Contracts\View\View
    {
        Log::debug('Start of noCategory()');
        $start ??= session('start');
        $end   ??= session('end');

        /** @var Carbon $start */
        /** @var Carbon $end */
        $page      = (int) $request->get('page');
        $pageSize  = (int) app('preferences')->get('listPageSize', 50)->data;
        $subTitle  = trans('firefly.without_category_between', ['start' => $start->isoFormat($this->monthAndDayFormat), 'end' => $end->isoFormat($this->monthAndDayFormat)]);
        $first     = $this->journalRepos->firstNull()->date ?? clone $start;
        $periods   = $this->getNoModelPeriodOverview('category', $first, $end);

        Log::debug(sprintf('Start for noCategory() is %s', $start->format('Y-m-d')));
        Log::debug(sprintf('End for noCategory() is %s', $end->format('Y-m-d')));

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)
            ->setLimit($pageSize)->setPage($page)->withoutCategory()
            ->withAccountInformation()->withBudgetInformation()
            ->setTypes([TransactionTypeEnum::WITHDRAWAL->value, TransactionTypeEnum::DEPOSIT->value, TransactionTypeEnum::TRANSFER->value])
        ;
        $groups    = $collector->getPaginatedGroups();
        $groups->setPath(route('categories.no-category', [$start->format('Y-m-d'), $end->format('Y-m-d')]));

        return view('categories.no-category', ['groups' => $groups, 'subTitle' => $subTitle, 'periods' => $periods, 'start' => $start, 'end' => $end]);
    }

    /**
     * Show all transactions without a category.
     *
     * @return Factory|View
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function showAll(Request $request): Factory|\Illuminate\Contracts\View\View
    {
        // default values:
        $start     = null;
        $end       = null;
        $periods   = new Collection();
        $page      = (int) $request->get('page');
        $pageSize  = (int) app('preferences')->get('listPageSize', 50)->data;
        Log::debug('Start of noCategory()');
        $subTitle  = (string) trans('firefly.all_journals_without_category');
        $first     = $this->journalRepos->firstNull();
        $start     = $first instanceof TransactionJournal ? $first->date : new Carbon();
        $end       = today(config('app.timezone'));
        Log::debug(sprintf('Start for noCategory() is %s', $start->format('Y-m-d')));
        Log::debug(sprintf('End for noCategory() is %s', $end->format('Y-m-d')));

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withoutCategory()
            ->withAccountInformation()->withBudgetInformation()
            ->setTypes([TransactionTypeEnum::WITHDRAWAL->value, TransactionTypeEnum::DEPOSIT->value, TransactionTypeEnum::TRANSFER->value])
        ;
        $groups    = $collector->getPaginatedGroups();
        $groups->setPath(route('categories.no-category.all'));

        return view('categories.no-category', ['groups' => $groups, 'subTitle' => $subTitle, 'periods' => $periods, 'start' => $start, 'end' => $end]);
    }
}
