<?php
/**
 * ShowController.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Budget;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 *
 * Class ShowController
 */
class ShowController extends Controller
{
    use PeriodOverview;

    /** @var BudgetRepositoryInterface The budget repository */
    private $repository;

    /**
     * ShowController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        app('view')->share('hideBudgets', true);

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
     * Show transactions without a budget.
     *
     * @param Request     $request
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function noBudget(Request $request, Carbon $start = null, Carbon $end = null)
    {
        /** @var Carbon $start */
        $start = $start ?? session('start');
        /** @var Carbon $end */
        $end      = $end ?? session('end');
        $subTitle = trans(
            'firefly.without_budget_between',
            ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
        );
        $periods  = $this->getBudgetPeriodOverview();
        $page     = (int)$request->get('page');
        $pageSize = (int)app('preferences')->get('listPageSize', 50)->data;

        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setLimit($pageSize)->setPage($page)
                  ->withoutBudget()->withOpposingAccount();
        $transactions = $collector->getPaginatedTransactions();
        $transactions->setPath(route('budgets.no-budget'));

        return view('budgets.no-budget', compact('transactions', 'subTitle', 'periods', 'start', 'end'));
    }

    /**
     * Shows ALL transactions without a budget.
     *
     * @param Request                    $request
     * @param JournalRepositoryInterface $repository
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function noBudgetAll(Request $request, JournalRepositoryInterface $repository)
    {
        $subTitle = (string)trans('firefly.all_journals_without_budget');
        $first    = $repository->firstNull();
        $start    = null === $first ? new Carbon : $first->date;
        $end      = new Carbon;
        $page     = (int)$request->get('page');
        $pageSize = (int)app('preferences')->get('listPageSize', 50)->data;

        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setLimit($pageSize)->setPage($page)
                  ->withoutBudget()->withOpposingAccount();
        $transactions = $collector->getPaginatedTransactions();
        $transactions->setPath(route('budgets.no-budget'));

        return view('budgets.no-budget', compact('transactions', 'subTitle', 'start', 'end'));
    }


    /**
     * Show a single budget.
     *
     * @param Request $request
     * @param Budget  $budget
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Request $request, Budget $budget)
    {
        /** @var Carbon $start */
        $start      = session('first', Carbon::now()->startOfYear());
        $end        = new Carbon;
        $page       = (int)$request->get('page');
        $pageSize   = (int)app('preferences')->get('listPageSize', 50)->data;
        $limits     = $this->getLimits($budget, $start, $end);
        $repetition = null;

        // collector:
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setBudget($budget)->setLimit($pageSize)->setPage($page)->withBudgetInformation();
        $transactions = $collector->getPaginatedTransactions();
        $transactions->setPath(route('budgets.show', [$budget->id]));

        $subTitle = (string)trans('firefly.all_journals_for_budget', ['name' => $budget->name]);

        return view('budgets.show', compact('limits', 'budget', 'repetition', 'transactions', 'subTitle'));
    }

    /**
     * Show a single budget by a budget limit.
     *
     * @param Request     $request
     * @param Budget      $budget
     * @param BudgetLimit $budgetLimit
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws FireflyException
     */
    public function showByBudgetLimit(Request $request, Budget $budget, BudgetLimit $budgetLimit)
    {
        if ($budgetLimit->budget->id !== $budget->id) {
            throw new FireflyException('This budget limit is not part of this budget.');
        }

        $page     = (int)$request->get('page');
        $pageSize = (int)app('preferences')->get('listPageSize', 50)->data;
        $subTitle = trans(
            'firefly.budget_in_period',
            [
                'name'  => $budget->name,
                'start' => $budgetLimit->start_date->formatLocalized($this->monthAndDayFormat),
                'end'   => $budgetLimit->end_date->formatLocalized($this->monthAndDayFormat),
            ]
        );

        // collector:
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($budgetLimit->start_date, $budgetLimit->end_date)
                  ->setBudget($budget)->setLimit($pageSize)->setPage($page)->withBudgetInformation();
        $transactions = $collector->getPaginatedTransactions();
        $transactions->setPath(route('budgets.show', [$budget->id, $budgetLimit->id]));
        /** @var Carbon $start */
        $start  = session('first', Carbon::now()->startOfYear());
        $end    = new Carbon;
        $limits = $this->getLimits($budget, $start, $end);

        return view('budgets.show', compact('limits', 'budget', 'budgetLimit', 'transactions', 'subTitle'));
    }

    /**
     * Gets all budget limits for a budget.
     *
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    protected function getLimits(Budget $budget, Carbon $start, Carbon $end): Collection // get data + augment with info
    {
        // properties for cache
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($budget->id);
        $cache->addProperty('get-limits');

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $set    = $this->repository->getBudgetLimits($budget, $start, $end);
        $limits = new Collection();

        /** @var BudgetLimit $entry */
        foreach ($set as $entry) {
            $entry->spent = $this->repository->spentInPeriod(new Collection([$budget]), new Collection(), $entry->start_date, $entry->end_date);
            $limits->push($entry);
        }
        $cache->store($limits);

        return $set;
    }
}
