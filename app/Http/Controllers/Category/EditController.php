<?php
declare(strict_types=1);
/**
 * EditController.php
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
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Http\Request;

/**
 * Class EditController
 */
class EditController extends Controller
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
     * Update category.
     *
     * @param CategoryFormRequest         $request
     * @param Category                    $category
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(CategoryFormRequest $request, Category $category)
    {
        $data = $request->getCategoryData();
        $this->repository->update($category, $data);

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
