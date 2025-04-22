<?php

/**
 * ShowController.php
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

use Carbon\Carbon;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\AugumentData;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    use AugumentData;
    use PeriodOverview;

    protected JournalRepositoryInterface $journalRepos;
    private BudgetRepositoryInterface    $repository;

    /**
     * ShowController constructor.
     */
    public function __construct()
    {
        app('view')->share('showCategory', true);
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.budgets'));
                app('view')->share('mainTitleIcon', 'fa-pie-chart');
                $this->journalRepos = app(JournalRepositoryInterface::class);
                $this->repository   = app(BudgetRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Show transactions without a budget.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function noBudget(Request $request, ?Carbon $start = null, ?Carbon $end = null)
    {
        $start ??= session('start');
        $end   ??= session('end');

        /** @var Carbon $start */
        /** @var Carbon $end */
        $subTitle  = trans(
            'firefly.without_budget_between',
            ['start' => $start->isoFormat($this->monthAndDayFormat), 'end' => $end->isoFormat($this->monthAndDayFormat)]
        );

        // get first journal ever to set off the budget period overview.
        $first     = $this->journalRepos->firstNull();
        $firstDate = null !== $first ? $first->date : $start;
        $periods   = $this->getNoBudgetPeriodOverview($firstDate, $end);
        $page      = (int) $request->get('page');
        $pageSize  = (int) app('preferences')->get('listPageSize', 50)->data;

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->setTypes([TransactionTypeEnum::WITHDRAWAL->value])->setLimit($pageSize)->setPage($page)
            ->withoutBudget()->withAccountInformation()->withCategoryInformation()
        ;
        $groups    = $collector->getPaginatedGroups();
        $groups->setPath(route('budgets.no-budget'));

        return view('budgets.no-budget', compact('groups', 'subTitle', 'periods', 'start', 'end'));
    }

    /**
     * Shows ALL transactions without a budget.
     *
     * @return Factory|View
     */
    public function noBudgetAll(Request $request)
    {
        $subTitle  = (string) trans('firefly.all_journals_without_budget');
        $first     = $this->journalRepos->firstNull();
        $start     = null === $first ? new Carbon() : $first->date;
        $end       = today(config('app.timezone'));
        $page      = (int) $request->get('page');
        $pageSize  = (int) app('preferences')->get('listPageSize', 50)->data;

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->setTypes([TransactionTypeEnum::WITHDRAWAL->value])->setLimit($pageSize)->setPage($page)
            ->withoutBudget()->withAccountInformation()->withCategoryInformation()
        ;
        $groups    = $collector->getPaginatedGroups();
        $groups->setPath(route('budgets.no-budget-all'));

        return view('budgets.no-budget', compact('groups', 'subTitle', 'start', 'end'));
    }

    /**
     * Show a single budget.
     *
     * @return Factory|View
     */
    public function show(Request $request, Budget $budget)
    {
        /** @var Carbon $allStart */
        $allStart    = session('first', today(config('app.timezone'))->startOfYear());
        $allEnd      = today();
        $page        = (int) $request->get('page');
        $pageSize    = (int) app('preferences')->get('listPageSize', 50)->data;
        $limits      = $this->getLimits($budget, $allStart, $allEnd);
        $repetition  = null;
        $attachments = $this->repository->getAttachments($budget);

        // collector:
        /** @var GroupCollectorInterface $collector */
        $collector   = app(GroupCollectorInterface::class);
        $collector->setRange($allStart, $allEnd)->setBudget($budget)
            ->withAccountInformation()
            ->setLimit($pageSize)->setPage($page)->withBudgetInformation()->withCategoryInformation()
        ;
        $groups      = $collector->getPaginatedGroups();
        $groups->setPath(route('budgets.show', [$budget->id]));

        $subTitle    = (string) trans('firefly.all_journals_for_budget', ['name' => $budget->name]);

        return view('budgets.show', compact('limits', 'attachments', 'budget', 'repetition', 'groups', 'subTitle'));
    }

    /**
     * Show a single budget by a budget limit.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function showByBudgetLimit(Request $request, Budget $budget, BudgetLimit $budgetLimit)
    {
        if ($budgetLimit->budget->id !== $budget->id) {
            throw new FireflyException('This budget limit is not part of this budget.');
        }

        $currencySymbol = $budgetLimit->transactionCurrency->symbol;
        $page           = (int) $request->get('page');
        $pageSize       = (int) app('preferences')->get('listPageSize', 50)->data;
        $subTitle       = trans(
            'firefly.budget_in_period',
            [
                'name'     => $budget->name,
                'start'    => $budgetLimit->start_date->isoFormat($this->monthAndDayFormat),
                'end'      => $budgetLimit->end_date->isoFormat($this->monthAndDayFormat),
                'currency' => $budgetLimit->transactionCurrency->name,
            ]
        );
        if ($this->convertToNative) {
            $currencySymbol = $this->defaultCurrency->symbol;
        }

        // collector:
        /** @var GroupCollectorInterface $collector */
        $collector      = app(GroupCollectorInterface::class);

        $collector->setRange($budgetLimit->start_date, $budgetLimit->end_date)->withAccountInformation()
            ->setBudget($budget)->setLimit($pageSize)->setPage($page)->withBudgetInformation()->withCategoryInformation()
        ;
        $groups         = $collector->getPaginatedGroups();
        $groups->setPath(route('budgets.show.limit', [$budget->id, $budgetLimit->id]));

        /** @var Carbon $start */
        $start          = session('first', today(config('app.timezone'))->startOfYear());
        $end            = today(config('app.timezone'));
        $attachments    = $this->repository->getAttachments($budget);
        $limits         = $this->getLimits($budget, $start, $end);

        return view('budgets.show', compact('limits', 'attachments', 'budget', 'budgetLimit', 'groups', 'subTitle', 'currencySymbol'));
    }
}
