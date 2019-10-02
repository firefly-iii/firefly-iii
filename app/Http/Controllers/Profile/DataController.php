<?php
/**
 * DataController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\Profile;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Http\RedirectResponse;

/**
 * Class DataController
 */
class DataController extends Controller
{

    /**
     *
     */
    public function deleteBudgets(): RedirectResponse
    {
        /** @var AvailableBudgetRepositoryInterface $abRepository */
        $abRepository = app(AvailableBudgetRepositoryInterface::class);
        $abRepository->destroyAll();

        /** @var BudgetLimitRepositoryInterface $blRepository */
        $blRepository = app(BudgetLimitRepositoryInterface::class);
        $blRepository->destroyAll();

        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = app(BudgetRepositoryInterface::class);
        $budgetRepository->destroyAll();

        session()->flash('success', trans('firefly.deleted_all_budgets'));

        return redirect(route('profile.index'));
    }

    /**
     *
     */
    public function deleteCategories(): RedirectResponse
    {
        /** @var CategoryRepositoryInterface $categoryRepos */
        $categoryRepos = app(CategoryRepositoryInterface::class);
        $categoryRepos->destroyAll();

        session()->flash('success', trans('firefly.deleted_all_categories'));

        return redirect(route('profile.index'));
    }


    /**
     *
     */
    public function deleteTags(): RedirectResponse
    {
        /** @var TagRepositoryInterface $tagRepository */
        $tagRepository = app(TagRepositoryInterface::class);
        $tagRepository->destroyAll();

        session()->flash('success', trans('firefly.deleted_all_tags'));

        return redirect(route('profile.index'));
    }
}