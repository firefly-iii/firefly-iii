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
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\BudgetFormUpdateRequest;
use FireflyIII\Models\AutoBudget;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 *
 * Class EditController
 */
class EditController extends Controller
{
    /** @var BudgetRepositoryInterface The budget repository */
    private $repository;

    /** @var AttachmentHelperInterface Helper for attachments. */
    private $attachments;

    /**
     * EditController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.budgets'));
                app('view')->share('mainTitleIcon', 'fa-pie-chart');
                $this->repository = app(BudgetRepositoryInterface::class);
                $this->attachments = app(AttachmentHelperInterface::class);

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
     * @return Factory|View
     */
    public function edit(Request $request, Budget $budget)
    {
        $subTitle   = (string) trans('firefly.edit_budget', ['name' => $budget->name]);
        $autoBudget = $this->repository->getAutoBudget($budget);

        // auto budget types
        $autoBudgetTypes   = [
            0                                => (string) trans('firefly.auto_budget_none'),
            AutoBudget::AUTO_BUDGET_RESET    => (string) trans('firefly.auto_budget_reset'),
            AutoBudget::AUTO_BUDGET_ROLLOVER => (string) trans('firefly.auto_budget_rollover'),
        ];
        $autoBudgetPeriods = [
            'daily'     => (string) trans('firefly.auto_budget_period_daily'),
            'weekly'    => (string) trans('firefly.auto_budget_period_weekly'),
            'monthly'   => (string) trans('firefly.auto_budget_period_monthly'),
            'quarterly' => (string) trans('firefly.auto_budget_period_quarterly'),
            'half_year' => (string) trans('firefly.auto_budget_period_half_year'),
            'yearly'    => (string) trans('firefly.auto_budget_period_yearly'),
        ];

        // code to handle active-checkboxes
        $hasOldInput = null !== $request->old('_token');
        $currency    = app('amount')->getDefaultCurrency();
        $preFilled   = [
            'active'                  => $hasOldInput ? (bool) $request->old('active') : $budget->active,
            'auto_budget_currency_id' => $hasOldInput ? (int) $request->old('auto_budget_currency_id') : $currency->id,
        ];
        if ($autoBudget) {
            $preFilled['auto_budget_amount'] = $hasOldInput ? $request->old('auto_budget_amount') : $autoBudget->amount;
        }

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('budgets.edit.fromUpdate')) {
            $this->rememberPreviousUri('budgets.edit.uri');
        }
        $request->session()->forget('budgets.edit.fromUpdate');
        $request->session()->flash('preFilled', $preFilled);

        return prefixView('budgets.edit', compact('budget', 'subTitle', 'autoBudgetTypes', 'autoBudgetPeriods', 'autoBudget'));
    }

    /**
     * Budget update routine.
     *
     * @param BudgetFormUpdateRequest $request
     * @param Budget                  $budget
     *
     * @return RedirectResponse
     */
    public function update(BudgetFormUpdateRequest $request, Budget $budget): RedirectResponse
    {
        $data = $request->getBudgetData();
        $this->repository->update($budget, $data);

        $request->session()->flash('success', (string) trans('firefly.updated_budget', ['name' => $budget->name]));
        $this->repository->cleanupBudgets();
        app('preferences')->mark();

        $redirect = redirect($this->getPreviousUri('budgets.edit.uri'));

        // store new attachment(s):
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($budget, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            session()->flash('info',(string)trans('firefly.no_att_demo_user'));
        }

        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments')); // @codeCoverageIgnore
        }

        if (1 === (int) $request->get('return_to_edit')) {
            // @codeCoverageIgnoreStart
            $request->session()->put('budgets.edit.fromUpdate', true);

            $redirect = redirect(route('budgets.edit', [$budget->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }
}
