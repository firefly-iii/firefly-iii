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

namespace FireflyIII\Http\Controllers\Category;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 *
 * Class ShowController
 *
 *
 */
class ShowController extends Controller
{
    use PeriodOverview;
    /** @var CategoryRepositoryInterface The category repository */
    private $repository;

    /**
     * CategoryController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.categories'));
                app('view')->share('mainTitleIcon', 'fa-bar-chart');
                $this->repository = app(CategoryRepositoryInterface::class);

                return $next($request);
            }
        );
    }



    /**
     * Show a single category.
     *
     * @param Request $request
     * @param Category $category
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Request $request, Category $category, Carbon $start = null, Carbon $end = null)
    {
        //Log::debug('Now in show()');
        /** @var Carbon $start */
        $start = $start ?? session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end          = $end ?? session('end', Carbon::now()->endOfMonth());
        $subTitleIcon = 'fa-bar-chart';
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $oldest       = $this->repository->firstUseDate($category) ?? Carbon::create()->startOfYear();
        $periods      = $this->getCategoryPeriodOverview($category, $oldest, $end);
        $path         = route('categories.show', [$category->id, $start->format('Y-m-d'), $end->format('Y-m-d')]);
        $subTitle     = trans(
            'firefly.journals_in_period_for_category',
            ['name' => $category->name, 'start' => $start->formatLocalized($this->monthAndDayFormat),
             'end'  => $end->formatLocalized($this->monthAndDayFormat),]
        );

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->setLimit($pageSize)->setPage($page)
                  ->withAccountInformation()
                  ->setCategory($category)->withBudgetInformation()->withCategoryInformation();

        $groups = $collector->getPaginatedGroups();
        $groups->setPath($path);

        //Log::debug('End of show()');

        return view('categories.show', compact('category', 'groups', 'periods', 'subTitle', 'subTitleIcon', 'start', 'end'));
    }

    /**
     * Show all transactions within a category.
     *
     * @param Request $request
     * @param Category $category
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showAll(Request $request, Category $category)
    {
        // default values:
        $subTitleIcon = 'fa-bar-chart';
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $start        = null;
        $end          = null;
        $periods      = new Collection;

        $subTitle = (string)trans('firefly.all_journals_for_category', ['name' => $category->name]);
        $first    = $this->repository->firstUseDate($category);
        /** @var Carbon $start */
        $start = $first ?? new Carbon;
        $end   = new Carbon;
        $path  = route('categories.show.all', [$category->id]);


        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->setLimit($pageSize)->setPage($page)
                  ->withAccountInformation()
                  ->setCategory($category)->withBudgetInformation()->withCategoryInformation();

        $groups = $collector->getPaginatedGroups();
        $groups->setPath($path);

        return view('categories.show', compact('category', 'groups', 'periods', 'subTitle', 'subTitleIcon', 'start', 'end'));
    }
}
