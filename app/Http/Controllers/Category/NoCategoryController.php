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
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

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
     * @throws FireflyException
     */
    public function show(Request $request, ?Carbon $start = null, ?Carbon $end = null)
    {
        app('log')->debug('Start of noCategory()');
        // @var Carbon $start
        $start ??= session('start');
        // @var Carbon $end
        $end   ??= session('end');
        $page      = (int) $request->get('page');
        $pageSize  = (int) app('preferences')->get('listPageSize', 50)->data;
        $subTitle  = trans(
            'firefly.without_category_between',
            ['start' => $start->isoFormat($this->monthAndDayFormat), 'end' => $end->isoFormat($this->monthAndDayFormat)]
        );
        $periods   = $this->getNoCategoryPeriodOverview($start);

        app('log')->debug(sprintf('Start for noCategory() is %s', $start->format('Y-m-d')));
        app('log')->debug(sprintf('End for noCategory() is %s', $end->format('Y-m-d')));

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)
            ->setLimit($pageSize)->setPage($page)->withoutCategory()
            ->withAccountInformation()->withBudgetInformation()
            ->setTypes([TransactionTypeEnum::WITHDRAWAL->value, TransactionTypeEnum::DEPOSIT->value, TransactionTypeEnum::TRANSFER->value])
        ;
        $groups    = $collector->getPaginatedGroups();
        $groups->setPath(route('categories.no-category', [$start->format('Y-m-d'), $end->format('Y-m-d')]));

        return view('categories.no-category', compact('groups', 'subTitle', 'periods', 'start', 'end'));
    }

    /**
     * Show all transactions without a category.
     *
     * @return Factory|View
     */
    public function showAll(Request $request)
    {
        // default values:
        $start     = null;
        $end       = null;
        $periods   = new Collection();
        $page      = (int) $request->get('page');
        $pageSize  = (int) app('preferences')->get('listPageSize', 50)->data;
        app('log')->debug('Start of noCategory()');
        $subTitle  = (string) trans('firefly.all_journals_without_category');
        $first     = $this->journalRepos->firstNull();
        $start     = null === $first ? new Carbon() : $first->date;
        $end       = today(config('app.timezone'));
        app('log')->debug(sprintf('Start for noCategory() is %s', $start->format('Y-m-d')));
        app('log')->debug(sprintf('End for noCategory() is %s', $end->format('Y-m-d')));

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withoutCategory()
            ->withAccountInformation()->withBudgetInformation()
            ->setTypes([TransactionTypeEnum::WITHDRAWAL->value, TransactionTypeEnum::DEPOSIT->value, TransactionTypeEnum::TRANSFER->value])
        ;
        $groups    = $collector->getPaginatedGroups();
        $groups->setPath(route('categories.no-category.all'));

        return view('categories.no-category', compact('groups', 'subTitle', 'periods', 'start', 'end'));
    }
}
