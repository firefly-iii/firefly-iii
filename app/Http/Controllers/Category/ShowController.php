<?php

/**
 * ShowController.php
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    use PeriodOverview;

    /** @var CategoryRepositoryInterface The category repository */
    private $repository;

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
                $this->repository = app(CategoryRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Show a single category.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function show(Request $request, Category $category, ?Carbon $start = null, ?Carbon $end = null)
    {
        $start ??= session('start', today(config('app.timezone'))->startOfMonth());
        $end   ??= session('end', today(config('app.timezone'))->endOfMonth());

        /** @var Carbon $start */
        /** @var Carbon $end */
        $subTitleIcon = 'fa-bookmark';
        $page         = (int) $request->get('page');
        $attachments  = $this->repository->getAttachments($category);
        $pageSize     = (int) app('preferences')->get('listPageSize', 50)->data;
        $oldest       = $this->repository->firstUseDate($category) ?? today(config('app.timezone'))->startOfYear();
        $periods      = $this->getCategoryPeriodOverview($category, $oldest, $end);
        $path         = route('categories.show', [$category->id, $start->format('Y-m-d'), $end->format('Y-m-d')]);
        $subTitle     = trans(
            'firefly.journals_in_period_for_category',
            [
                'name'  => $category->name,
                'start' => $start->isoFormat($this->monthAndDayFormat),
                'end'   => $end->isoFormat($this->monthAndDayFormat),
            ]
        );

        /** @var GroupCollectorInterface $collector */
        $collector    = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->setLimit($pageSize)->setPage($page)
            ->withAccountInformation()
            ->setCategory($category)->withBudgetInformation()->withCategoryInformation()
        ;

        $groups       = $collector->getPaginatedGroups();
        $groups->setPath($path);

        return view('categories.show', compact('category', 'attachments', 'groups', 'periods', 'subTitle', 'subTitleIcon', 'start', 'end'));
    }

    /**
     * Show all transactions within a category.
     *
     * @return Factory|View
     */
    public function showAll(Request $request, Category $category)
    {
        // default values:
        $subTitleIcon = 'fa-bookmark';
        $page         = (int) $request->get('page');
        $pageSize     = (int) app('preferences')->get('listPageSize', 50)->data;
        $start        = null;
        $end          = null;
        $periods      = new Collection();

        $subTitle     = (string) trans('firefly.all_journals_for_category', ['name' => $category->name]);
        $first        = $this->repository->firstUseDate($category);

        /** @var Carbon $start */
        $start        = $first ?? today(config('app.timezone'));
        $end          = today(config('app.timezone'));
        $path         = route('categories.show.all', [$category->id]);
        $attachments  = $this->repository->getAttachments($category);

        /** @var GroupCollectorInterface $collector */
        $collector    = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->setLimit($pageSize)->setPage($page)
            ->withAccountInformation()
            ->setCategory($category)->withBudgetInformation()->withCategoryInformation()
        ;

        $groups       = $collector->getPaginatedGroups();
        $groups->setPath($path);

        return view('categories.show', compact('category', 'attachments', 'groups', 'periods', 'subTitle', 'subTitleIcon', 'start', 'end'));
    }
}
