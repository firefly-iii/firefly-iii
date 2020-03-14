<?php
/**
 * CreateController.php
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


use FireflyIII\Models\AutoBudget;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\BudgetFormStoreRequest;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Class CreateController
 */
class CreateController extends Controller
{
    /** @var BudgetRepositoryInterface The budget repository */
    private $repository;

    /**
     * CreateController constructor.
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
     * Form to create a budget.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(Request $request)
    {
        $hasOldInput = null !== $request->old('_token');

        // auto budget types
        $autoBudgetTypes = [
            0 => (string)trans('firefly.auto_budget_none'),
            AutoBudget::AUTO_BUDGET_RESET => (string)trans('firefly.auto_budget_reset'),
            AutoBudget::AUTO_BUDGET_ROLLOVER => (string)trans('firefly.auto_budget_rollover'),
        ];
        $autoBudgetPeriods = [
            'daily'     => (string)trans('firefly.auto_budget_period_daily'),
            'weekly'    => (string)trans('firefly.auto_budget_period_weekly'),
            'monthly'   => (string)trans('firefly.auto_budget_period_monthly'),
            'quarterly' => (string)trans('firefly.auto_budget_period_quarterly'),
            'half_year' => (string)trans('firefly.auto_budget_period_half_year'),
            'yearly'    => (string)trans('firefly.auto_budget_period_yearly'),
        ];
        $currency          = app('amount')->getDefaultCurrency();

        $preFilled = [
            'auto_budget_period'      => $hasOldInput ? (bool)$request->old('auto_budget_period') : 'monthly',
            'auto_budget_currency_id' => $hasOldInput ? (int)$request->old('auto_budget_currency_id') : $currency->id,
        ];

        $request->session()->flash('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('budgets.create.fromStore')) {
            $this->rememberPreviousUri('budgets.create.uri');
        }
        $request->session()->forget('budgets.create.fromStore');
        $subTitle = (string)trans('firefly.create_new_budget');

        return view('budgets.create', compact('subTitle', 'autoBudgetTypes', 'autoBudgetPeriods'));
    }


    /**
     * Stores a budget.
     *
     * @param BudgetFormStoreRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(BudgetFormStoreRequest $request): RedirectResponse
    {
        $data   = $request->getBudgetData();

        $budget = $this->repository->store($data);
        $this->repository->cleanupBudgets();
        $request->session()->flash('success', (string)trans('firefly.stored_new_budget', ['name' => $budget->name]));
        app('preferences')->mark();

        $redirect = redirect($this->getPreviousUri('budgets.create.uri'));

        if (1 === (int)$request->get('create_another')) {
            // @codeCoverageIgnoreStart
            $request->session()->put('budgets.create.fromStore', true);

            $redirect = redirect(route('budgets.create'))->withInput();
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }
}
