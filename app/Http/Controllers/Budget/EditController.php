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
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Class EditController
 */
class EditController extends Controller
{
    private AttachmentHelperInterface $attachments;
    private BudgetRepositoryInterface $repository;

    /**
     * EditController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.budgets'));
                app('view')->share('mainTitleIcon', 'fa-pie-chart');
                $this->repository  = app(BudgetRepositoryInterface::class);
                $this->attachments = app(AttachmentHelperInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Budget edit form.
     *
     * @return Factory|View
     */
    public function edit(Request $request, Budget $budget)
    {
        $subTitle          = (string) trans('firefly.edit_budget', ['name' => $budget->name]);
        $autoBudget        = $this->repository->getAutoBudget($budget);

        // auto budget types
        $autoBudgetTypes   = [
            0                                => (string) trans('firefly.auto_budget_none'),
            AutoBudget::AUTO_BUDGET_RESET    => (string) trans('firefly.auto_budget_reset'),
            AutoBudget::AUTO_BUDGET_ROLLOVER => (string) trans('firefly.auto_budget_rollover'),
            AutoBudget::AUTO_BUDGET_ADJUSTED => (string) trans('firefly.auto_budget_adjusted'),
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
        $hasOldInput       = null !== $request->old('_token');
        $currency          = app('amount')->getDefaultCurrency();
        $preFilled         = [
            'active'                  => $hasOldInput ? (bool) $request->old('active') : $budget->active,
            'auto_budget_currency_id' => $hasOldInput ? (int) $request->old('auto_budget_currency_id') : $currency->id,
        ];
        if (null !== $autoBudget) {
            $amount                          = $hasOldInput ? $request->old('auto_budget_amount') : $autoBudget->amount;
            if (is_array($amount)) {
                $amount = '0';
            }
            $amount                          = (string) $amount;
            $preFilled['auto_budget_amount'] = app('steam')->bcround($amount, $autoBudget->transactionCurrency->decimal_places);
        }

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('budgets.edit.fromUpdate')) {
            $this->rememberPreviousUrl('budgets.edit.url');
        }
        $request->session()->forget('budgets.edit.fromUpdate');
        $request->session()->flash('preFilled', $preFilled);

        return view('budgets.edit', compact('budget', 'subTitle', 'autoBudgetTypes', 'autoBudgetPeriods', 'autoBudget'));
    }

    /**
     * Budget update routine.
     */
    public function update(BudgetFormUpdateRequest $request, Budget $budget): RedirectResponse
    {
        $data     = $request->getBudgetData();
        $this->repository->update($budget, $data);

        $request->session()->flash('success', (string) trans('firefly.updated_budget', ['name' => $budget->name]));
        $this->repository->cleanupBudgets();
        app('preferences')->mark();

        Log::channel('audit')->info(sprintf('Updated budget #%d.', $budget->id), $data);

        $redirect = redirect($this->getPreviousUrl('budgets.edit.url'));

        // store new attachment(s):
        /** @var null|array $files */
        $files    = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($budget, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            Log::channel('audit')->warning(sprintf('The demo user is trying to upload attachments in %s.', __METHOD__));
            session()->flash('info', (string) trans('firefly.no_att_demo_user'));
        }

        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments'));
        }

        if (1 === (int) $request->get('return_to_edit')) {
            $request->session()->put('budgets.edit.fromUpdate', true);

            $redirect = redirect(route('budgets.edit', [$budget->id]))->withInput(['return_to_edit' => 1]);
        }

        return $redirect;
    }
}
