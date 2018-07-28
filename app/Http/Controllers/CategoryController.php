<?php
/**
 * CategoryController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers;

use FireflyIII\Http\Requests\CategoryFormRequest;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Class CategoryController.
 */
class CategoryController extends Controller
{
    /** @var CategoryRepositoryInterface The category repository */
    private $repository;

    /**
     * CategoryController constructor.
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
     * Create category.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(Request $request)
    {
        if (true !== session('categories.create.fromStore')) {
            $this->rememberPreviousUri('categories.create.uri');
        }
        $request->session()->forget('categories.create.fromStore');
        $subTitle = (string)trans('firefly.create_new_category');

        return view('categories.create', compact('subTitle'));
    }

    /**
     * Delete a category.
     *
     * @param Category $category
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function delete(Category $category)
    {
        $subTitle = (string)trans('firefly.delete_category', ['name' => $category->name]);

        // put previous url in session
        $this->rememberPreviousUri('categories.delete.uri');

        return view('categories.delete', compact('category', 'subTitle'));
    }

    /**
     * Destroy a category.
     *
     * @param Request  $request
     * @param Category $category
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, Category $category)
    {
        $name = $category->name;
        $this->repository->destroy($category);

        $request->session()->flash('success', (string)trans('firefly.deleted_category', ['name' => $name]));
        app('preferences')->mark();

        return redirect($this->getPreviousUri('categories.delete.uri'));
    }

    /**
     * Edit a category.
     *
     * @param Request  $request
     * @param Category $category
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Request $request, Category $category)
    {
        $subTitle = (string)trans('firefly.edit_category', ['name' => $category->name]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('categories.edit.fromUpdate')) {
            $this->rememberPreviousUri('categories.edit.uri');
        }
        $request->session()->forget('categories.edit.fromUpdate');

        return view('categories.edit', compact('category', 'subTitle'));
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


    /**
     * Store new category.
     *
     * @param CategoryFormRequest         $request
     * @param CategoryRepositoryInterface $repository
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(CategoryFormRequest $request, CategoryRepositoryInterface $repository)
    {
        $data     = $request->getCategoryData();
        $category = $repository->store($data);

        $request->session()->flash('success', (string)trans('firefly.stored_category', ['name' => $category->name]));
        app('preferences')->mark();

        $redirect = redirect(route('categories.index'));
        if (1 === (int)$request->get('create_another')) {
            // @codeCoverageIgnoreStart
            $request->session()->put('categories.create.fromStore', true);

            $redirect = redirect(route('categories.create'))->withInput();
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }


    /**
     * Update category.
     *
     * @param CategoryFormRequest         $request
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(CategoryFormRequest $request, CategoryRepositoryInterface $repository, Category $category)
    {
        $data = $request->getCategoryData();
        $repository->update($category, $data);

        $request->session()->flash('success', (string)trans('firefly.updated_category', ['name' => $category->name]));
        app('preferences')->mark();

        $redirect = redirect($this->getPreviousUri('categories.edit.uri'));

        if (1 === (int)$request->get('return_to_edit')) {
            // @codeCoverageIgnoreStart
            $request->session()->put('categories.edit.fromUpdate', true);

            $redirect = redirect(route('categories.edit', [$category->id]));
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }


}
