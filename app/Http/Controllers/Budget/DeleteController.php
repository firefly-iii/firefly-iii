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

namespace FireflyIII\Http\Controllers\Budget;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
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
    /** @var BudgetRepositoryInterface The budget repository */
    private $repository;

    /**
     * DeleteController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.budgets'));
                app('view')->share('mainTitleIcon', 'fa-pie-chart');
                $this->repository = app(BudgetRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Deletes a budget.
     *
     * @return Factory|View
     */
    public function delete(Budget $budget)
    {
        $subTitle = (string) trans('firefly.delete_budget', ['name' => $budget->name]);

        // put previous url in session
        $this->rememberPreviousUrl('budgets.delete.url');

        return view('budgets.delete', compact('budget', 'subTitle'));
    }

    /**
     * Destroys a budget.
     *
     * @return Redirector|RedirectResponse
     */
    public function destroy(Request $request, Budget $budget)
    {
        $name = $budget->name;
        $this->repository->destroy($budget);
        $request->session()->flash('success', (string) trans('firefly.deleted_budget', ['name' => $name]));
        app('preferences')->mark();

        return redirect($this->getPreviousUrl('budgets.delete.url'));
    }
}
