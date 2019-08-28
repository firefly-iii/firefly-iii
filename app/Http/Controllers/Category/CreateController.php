<?php
declare(strict_types=1);
/**
 * CreateController.php
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
use FireflyIII\Http\Requests\CategoryFormRequest;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Http\Request;

/**
 * Class CreateController
 */
class CreateController extends Controller
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
     * Store new category.
     *
     * @param CategoryFormRequest $request
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(CategoryFormRequest $request)
    {
        $data     = $request->getCategoryData();
        $category = $this->repository->store($data);

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
}
