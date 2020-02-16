<?php
/**
 * EditController.php
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
use FireflyIII\Http\Requests\BudgetFormRequest;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 *
 * Class EditController
 */
class EditController extends Controller
{
    /** @var BudgetRepositoryInterface The budget repository */
    private $repository;

    /**
     * EditController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.budgets'));
                app('view')->share('mainTitleIcon', 'fa-tasks');
                $this->repository = app(BudgetRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Budget edit form.
     *
     * @param Request $request
     * @param Budget  $budget
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Request $request, Budget $budget)
    {
        $subTitle = (string)trans('firefly.edit_budget', ['name' => $budget->name]);

        // code to handle active-checkboxes
        $hasOldInput = null !== $request->old('_token');
        $preFilled   = [
            'active' => $hasOldInput ? (bool)$request->old('active') : $budget->active,
        ];

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('budgets.edit.fromUpdate')) {
            $this->rememberPreviousUri('budgets.edit.uri');
        }
        $request->session()->forget('budgets.edit.fromUpdate');
        $request->session()->flash('preFilled', $preFilled);

        return view('budgets.edit', compact('budget', 'subTitle'));
    }

    /**
     * Budget update routine.
     *
     * @param BudgetFormRequest $request
     * @param Budget            $budget
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(BudgetFormRequest $request, Budget $budget): RedirectResponse
    {
        $data = $request->getBudgetData();
        $this->repository->update($budget, $data);

        $request->session()->flash('success', (string)trans('firefly.updated_budget', ['name' => $budget->name]));
        $this->repository->cleanupBudgets();
        app('preferences')->mark();

        $redirect = redirect($this->getPreviousUri('budgets.edit.uri'));

        if (1 === (int)$request->get('return_to_edit')) {
            // @codeCoverageIgnoreStart
            $request->session()->put('budgets.edit.fromUpdate', true);

            $redirect = redirect(route('budgets.edit', [$budget->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }
}
