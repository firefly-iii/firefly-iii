<?php
declare(strict_types=1);
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

namespace FireflyIII\Http\Controllers\Category;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
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
     * Show all categories.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $page       = 0 === (int)$request->get('page') ? 1 : (int)$request->get('page');
        $pageSize   = (int)app('preferences')->get('listPageSize', 50)->data;
        $collection = $this->repository->getCategories();
        $total      = $collection->count();
        $collection = $collection->slice(($page - 1) * $pageSize, $pageSize);

        $collection->each(
            function (Category $category) {
                $category->lastActivity = $this->repository->lastUseDate($category, new Collection);
            }
        );

        // paginate categories
        $categories = new LengthAwarePaginator($collection, $total, $pageSize, $page);
        $categories->setPath(route('categories.index'));

        return view('categories.index', compact('categories'));
    }

}
