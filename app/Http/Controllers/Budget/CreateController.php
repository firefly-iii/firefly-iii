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

use FireflyIII\Enums\AutoBudgetType;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\BudgetFormStoreRequest;
use FireflyIII\Models\AutoBudget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Class CreateController
 */
class CreateController extends Controller
{
    private AttachmentHelperInterface $attachments;
    private BudgetRepositoryInterface $repository;

    /**
     * CreateController constructor.
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
     * Form to create a budget.
     *
     * @return Factory|View
     */
    public function create(Request $request)
    {
        $hasOldInput       = null !== $request->old('_token');

        // auto budget types
        $autoBudgetTypes   = [
            0                                => (string) trans('firefly.auto_budget_none'),
            AutoBudgetType::AUTO_BUDGET_RESET->value    => (string) trans('firefly.auto_budget_reset'),
            AutoBudgetType::AUTO_BUDGET_ROLLOVER->value => (string) trans('firefly.auto_budget_rollover'),
            AutoBudgetType::AUTO_BUDGET_ADJUSTED->value => (string) trans('firefly.auto_budget_adjusted'),
        ];
        $autoBudgetPeriods = [
            'daily'     => (string) trans('firefly.auto_budget_period_daily'),
            'weekly'    => (string) trans('firefly.auto_budget_period_weekly'),
            'monthly'   => (string) trans('firefly.auto_budget_period_monthly'),
            'quarterly' => (string) trans('firefly.auto_budget_period_quarterly'),
            'half_year' => (string) trans('firefly.auto_budget_period_half_year'),
            'yearly'    => (string) trans('firefly.auto_budget_period_yearly'),
        ];

        $preFilled         = [
            'auto_budget_period'      => $hasOldInput ? (bool) $request->old('auto_budget_period') : 'monthly',
            'auto_budget_currency_id' => $hasOldInput ? (int) $request->old('auto_budget_currency_id') : $this->defaultCurrency->id,
        ];

        $request->session()->flash('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('budgets.create.fromStore')) {
            $this->rememberPreviousUrl('budgets.create.url');
        }
        $request->session()->forget('budgets.create.fromStore');
        $subTitle          = (string) trans('firefly.create_new_budget');

        return view('budgets.create', compact('subTitle', 'autoBudgetTypes', 'autoBudgetPeriods'));
    }

    /**
     * Stores a budget.
     *
     * @throws FireflyException
     */
    public function store(BudgetFormStoreRequest $request): RedirectResponse
    {
        $data     = $request->getBudgetData();

        $budget   = $this->repository->store($data);
        $this->repository->cleanupBudgets();
        $request->session()->flash('success', (string) trans('firefly.stored_new_budget', ['name' => $budget->name]));
        app('preferences')->mark();

        Log::channel('audit')->info('Stored new budget.', $data);

        // store attachment(s):
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

        $redirect = redirect($this->getPreviousUrl('budgets.create.url'));

        if (1 === (int) $request->get('create_another')) {
            $request->session()->put('budgets.create.fromStore', true);

            $redirect = redirect(route('budgets.create'))->withInput();
        }

        return $redirect;
    }
}
