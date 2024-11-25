<?php

/**
 * DeleteController.php
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

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class DeleteController
 */
class DeleteController extends Controller
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
                app('view')->share('mainTitleIcon', 'fa-bookmark');
                $this->repository = app(CategoryRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Delete a category.
     *
     * @return Factory|View
     */
    public function delete(Category $category)
    {
        $subTitle = (string)trans('firefly.delete_category', ['name' => $category->name]);

        // put previous url in session
        $this->rememberPreviousUrl('categories.delete.url');

        return view('categories.delete', compact('category', 'subTitle'));
    }

    /**
     * Destroy a category.
     *
     * @return Redirector|RedirectResponse
     */
    public function destroy(Request $request, Category $category)
    {
        $name = $category->name;
        $this->repository->destroy($category);

        $request->session()->flash('success', (string)trans('firefly.deleted_category', ['name' => $name]));
        app('preferences')->mark();

        return redirect($this->getPreviousUrl('categories.delete.url'));
    }
}
