<?php
/**
 * BudgetController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Requests\BudgetFormRequest;
use FireflyIII\Http\Requests\BudgetIncomeRequest;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use Preferences;
use Response;
use View;

/**
 * Class BudgetController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BudgetController extends Controller
{
    /** @var BudgetRepositoryInterface */
    private $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        View::share('hideBudgets', true);

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', trans('firefly.budgets'));
                app('view')->share('mainTitleIcon', 'fa-tasks');
                $this->repository = app(BudgetRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Request                   $request
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function amount(Request $request, BudgetRepositoryInterface $repository, Budget $budget)
    {
        $amount = strval($request->get('amount'));
        $start  = Carbon::createFromFormat('Y-m-d', $request->get('start'));
        $end    = Carbon::createFromFormat('Y-m-d', $request->get('end'));
        $budgetLimit = $this->repository->updateLimitAmount($budget, $start, $end, $amount);
        if (0 === bccomp($amount, '0')) {
            $budgetLimit = null;
        }

        // calculate left in budget:
        $spent    = $repository->spentInPeriod(new Collection([$budget]), new Collection, $start, $end);
        $currency = app('amount')->getDefaultCurrency();
        $left     = app('amount')->formatAnything($currency, bcadd($amount, $spent), true);

        Preferences::mark();

        return Response::json(['left' => $left, 'name' => $budget->name, 'limit' => $budgetLimit ? $budgetLimit->id : 0, 'amount' => $amount]);
    }

    /**
     * @param Request $request
     *
     * @return View
     */
    public function create(Request $request)
    {
        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('budgets.create.fromStore')) {
            $this->rememberPreviousUri('budgets.create.uri');
        }
        $request->session()->forget('budgets.create.fromStore');
        $subTitle = (string)trans('firefly.create_new_budget');

        return view('budgets.create', compact('subTitle'));
    }

    /**
     * @param Budget $budget
     *
     * @return View
     */
    public function delete(Budget $budget)
    {
        $subTitle = trans('firefly.delete_budget', ['name' => $budget->name]);

        // put previous url in session
        $this->rememberPreviousUri('budgets.delete.uri');

        return view('budgets.delete', compact('budget', 'subTitle'));
    }

    /**
     * @param Request $request
     * @param Budget  $budget
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, Budget $budget)
    {
        $name = $budget->name;
        $this->repository->destroy($budget);
        $request->session()->flash('success', strval(trans('firefly.deleted_budget', ['name' => $name])));
        Preferences::mark();

        return redirect($this->getPreviousUri('budgets.delete.uri'));
    }

    /**
     * @param Request $request
     * @param Budget  $budget
     *
     * @return View
     */
    public function edit(Request $request, Budget $budget)
    {
        $subTitle = trans('firefly.edit_budget', ['name' => $budget->name]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('budgets.edit.fromUpdate')) {
            $this->rememberPreviousUri('budgets.edit.uri');
        }
        $request->session()->forget('budgets.edit.fromUpdate');

        return view('budgets.edit', compact('budget', 'subTitle'));
    }

    /**
     * @param string|null $moment
     *
     * @return View
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) complex because of while loop
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function index(Request $request, string $moment = null)
    {
        $range    = Preferences::get('viewRange', '1M')->data;
        $start    = session('start', new Carbon);
        $end      = session('end', new Carbon);
        $page     = 0 === intval($request->get('page')) ? 1 : intval($request->get('page'));
        $pageSize = intval(Preferences::get('listPageSize', 50)->data);

        // make date if present:
        if (null !== $moment || 0 !== strlen(strval($moment))) {
            try {
                $start = new Carbon($moment);
                $end   = app('navigation')->endOfPeriod($start, $range);
            } catch (Exception $e) {
                // start and end are already defined.
            }
        }
        $next = clone $end;
        $next->addDay();
        $prev = clone $start;
        $prev->subDay();
        $prev = app('navigation')->startOfPeriod($prev, $range);
        $this->repository->cleanupBudgets();
        $allBudgets        = $this->repository->getActiveBudgets();
        $total             = $allBudgets->count();
        $budgets           = $allBudgets->slice(($page - 1) * $pageSize, $pageSize);
        $inactive          = $this->repository->getInactiveBudgets();
        $periodStart       = $start->formatLocalized($this->monthAndDayFormat);
        $periodEnd         = $end->formatLocalized($this->monthAndDayFormat);
        $budgetInformation = $this->repository->collectBudgetInformation($allBudgets, $start, $end);
        $defaultCurrency   = app('amount')->getDefaultCurrency();
        $available         = $this->repository->getAvailableBudget($defaultCurrency, $start, $end);
        $spent             = array_sum(array_column($budgetInformation, 'spent'));
        $budgeted          = array_sum(array_column($budgetInformation, 'budgeted'));

        // paginate budgets
        $budgets = new LengthAwarePaginator($budgets, $total, $pageSize, $page);
        $budgets->setPath(route('budgets.index'));

        // select thing for last 12 periods:
        $previousLoop = [];
        $previousDate = clone $start;
        $count        = 0;
        while ($count < 12) {
            $previousDate->subDay();
            $previousDate          = app('navigation')->startOfPeriod($previousDate, $range);
            $format                = $previousDate->format('Y-m-d');
            $previousLoop[$format] = app('navigation')->periodShow($previousDate, $range);
            ++$count;
        }

        // select thing for next 12 periods:
        $nextLoop = [];
        $nextDate = clone $end;
        $nextDate->addDay();
        $count = 0;

        while ($count < 12) {
            $format            = $nextDate->format('Y-m-d');
            $nextLoop[$format] = app('navigation')->periodShow($nextDate, $range);
            $nextDate          = app('navigation')->endOfPeriod($nextDate, $range);
            ++$count;
            $nextDate->addDay();
        }

        // display info
        $currentMonth = app('navigation')->periodShow($start, $range);
        $nextText     = app('navigation')->periodShow($next, $range);
        $prevText     = app('navigation')->periodShow($prev, $range);

        return view(
            'budgets.index',
            compact(
                'available',
                'currentMonth',
                'next',
                'nextText',
                'prev', 'allBudgets',
                'prevText',
                'periodStart',
                'periodEnd',
                'page',
                'budgetInformation',
                'inactive',
                'budgets',
                'spent',
                'budgeted',
                'previousLoop',
                'nextLoop',
                'start',
                'end'
            )
        );
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function infoIncome(Carbon $start, Carbon $end)
    {
        // properties for cache
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('info-income');

        Log::debug(sprintf('infoIncome start is %s', $start->format('Y-m-d')));
        Log::debug(sprintf('infoIncome end is %s', $end->format('Y-m-d')));

        if ($cache->has()) {
            // @codeCoverageIgnoreStart
            $result = $cache->get();

            return view('budgets.info', compact('result', 'begin', 'currentEnd'));
            // @codeCoverageIgnoreEnd
        }
        $result   = [
            'available' => '0',
            'earned'    => '0',
            'suggested' => '0',
        ];
        $currency = app('amount')->getDefaultCurrency();
        $range    = Preferences::get('viewRange', '1M')->data;
        $begin    = app('navigation')->subtractPeriod($start, $range, 3);

        Log::debug(sprintf('Range is %s', $range));
        Log::debug(sprintf('infoIncome begin is %s', $begin->format('Y-m-d')));

        // get average amount available.
        $total        = '0';
        $count        = 0;
        $currentStart = clone $begin;
        while ($currentStart < $start) {
            Log::debug(sprintf('Loop: currentStart is %s', $currentStart->format('Y-m-d')));
            $currentEnd   = app('navigation')->endOfPeriod($currentStart, $range);
            $total        = bcadd($total, $this->repository->getAvailableBudget($currency, $currentStart, $currentEnd));
            $currentStart = app('navigation')->addPeriod($currentStart, $range, 0);
            ++$count;
        }
        Log::debug('Loop end');

        if (0 === $count) {
            $count = 1;
        }
        $result['available'] = bcdiv($total, strval($count));

        // amount earned in this period:
        $subDay = clone $end;
        $subDay->subDay();
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($begin, $subDay)->setTypes([TransactionType::DEPOSIT])->withOpposingAccount();
        $result['earned'] = bcdiv(strval($collector->getJournals()->sum('transaction_amount')), strval($count));

        // amount spent in period
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($begin, $subDay)->setTypes([TransactionType::WITHDRAWAL])->withOpposingAccount();
        $result['spent'] = bcdiv(strval($collector->getJournals()->sum('transaction_amount')), strval($count));
        // suggestion starts with the amount spent
        $result['suggested'] = bcmul($result['spent'], '-1');
        $result['suggested'] = 1 === bccomp($result['suggested'], $result['earned']) ? $result['earned'] : $result['suggested'];
        // unless it's more than you earned. So min() of suggested/earned

        $cache->store($result);

        return view('budgets.info', compact('result', 'begin', 'currentEnd'));
    }

    /**
     * @param Request                    $request
     * @param JournalRepositoryInterface $repository
     * @param string                     $moment
     *
     * @return View
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function noBudget(Request $request, JournalRepositoryInterface $repository, string $moment = '')
    {
        // default values:
        $range   = Preferences::get('viewRange', '1M')->data;
        $start   = null;
        $end     = null;
        $periods = new Collection;

        // prep for "all" view.
        if ('all' === $moment) {
            $subTitle = trans('firefly.all_journals_without_budget');
            $first    = $repository->first();
            $start    = $first->date ?? new Carbon;
            $end      = new Carbon;
        }

        // prep for "specific date" view.
        if (strlen($moment) > 0 && 'all' !== $moment) {
            $start    = new Carbon($moment);
            $end      = app('navigation')->endOfPeriod($start, $range);
            $subTitle = trans(
                'firefly.without_budget_between',
                ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
            );
            $periods  = $this->getPeriodOverview();
        }

        // prep for current period
        if (0 === strlen($moment)) {
            $start    = clone session('start', app('navigation')->startOfPeriod(new Carbon, $range));
            $end      = clone session('end', app('navigation')->endOfPeriod(new Carbon, $range));
            $periods  = $this->getPeriodOverview();
            $subTitle = trans(
                'firefly.without_budget_between',
                ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
            );
        }

        $page     = intval($request->get('page'));
        $pageSize = intval(Preferences::get('listPageSize', 50)->data);

        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setLimit($pageSize)->setPage($page)
                  ->withoutBudget()->withOpposingAccount();
        $transactions = $collector->getPaginatedJournals();
        $transactions->setPath(route('budgets.no-budget'));

        return view('budgets.no-budget', compact('transactions', 'subTitle', 'moment', 'periods', 'start', 'end'));
    }

    /**
     * @param BudgetIncomeRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postUpdateIncome(BudgetIncomeRequest $request)
    {
        $start           = Carbon::createFromFormat('Y-m-d', $request->string('start'));
        $end             = Carbon::createFromFormat('Y-m-d', $request->string('end'));
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $amount          = $request->get('amount');
        $page            = $request->integer('page') === 0 ? 1 : $request->integer('page');
        $this->repository->cleanupBudgets();
        $this->repository->setAvailableBudget($defaultCurrency, $start, $end, $amount);
        Preferences::mark();

        return redirect(route('budgets.index', [$start->format('Y-m-d')]) . '?page=' . $page);
    }

    /**
     * @param Request $request
     * @param Budget  $budget
     *
     * @return View
     */
    public function show(Request $request, Budget $budget)
    {
        /** @var Carbon $start */
        $start      = session('first', Carbon::create()->startOfYear());
        $end        = new Carbon;
        $page       = intval($request->get('page'));
        $pageSize   = intval(Preferences::get('listPageSize', 50)->data);
        $limits     = $this->getLimits($budget, $start, $end);
        $repetition = null;
        // collector:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setBudget($budget)->setLimit($pageSize)->setPage($page)->withBudgetInformation();
        $transactions = $collector->getPaginatedJournals();
        $transactions->setPath(route('budgets.show', [$budget->id]));

        $subTitle = trans('firefly.all_journals_for_budget', ['name' => $budget->name]);

        return view('budgets.show', compact('limits', 'budget', 'repetition', 'transactions', 'subTitle'));
    }

    /**
     * @param Request     $request
     * @param Budget      $budget
     * @param BudgetLimit $budgetLimit
     *
     * @return View
     *
     * @throws FireflyException
     */
    public function showByBudgetLimit(Request $request, Budget $budget, BudgetLimit $budgetLimit)
    {
        if ($budgetLimit->budget->id !== $budget->id) {
            throw new FireflyException('This budget limit is not part of this budget.');
        }

        $page     = intval($request->get('page'));
        $pageSize = intval(Preferences::get('listPageSize', 50)->data);
        $subTitle = trans(
            'firefly.budget_in_period',
            [
                'name'  => $budget->name,
                'start' => $budgetLimit->start_date->formatLocalized($this->monthAndDayFormat),
                'end'   => $budgetLimit->end_date->formatLocalized($this->monthAndDayFormat),
            ]
        );

        // collector:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($budgetLimit->start_date, $budgetLimit->end_date)
                  ->setBudget($budget)->setLimit($pageSize)->setPage($page)->withBudgetInformation();
        $transactions = $collector->getPaginatedJournals();
        $transactions->setPath(route('budgets.show', [$budget->id, $budgetLimit->id]));
        $start  = session('first', Carbon::create()->startOfYear());
        $end    = new Carbon;
        $limits = $this->getLimits($budget, $start, $end);

        return view('budgets.show', compact('limits', 'budget', 'budgetLimit', 'transactions', 'subTitle'));
    }

    /**
     * @param BudgetFormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(BudgetFormRequest $request)
    {
        $data   = $request->getBudgetData();
        $budget = $this->repository->store($data);
        $this->repository->cleanupBudgets();
        $request->session()->flash('success', strval(trans('firefly.stored_new_budget', ['name' => $budget->name])));
        Preferences::mark();

        if (1 === intval($request->get('create_another'))) {
            // @codeCoverageIgnoreStart
            $request->session()->put('budgets.create.fromStore', true);

            return redirect(route('budgets.create'))->withInput();
            // @codeCoverageIgnoreEnd
        }

        return redirect($this->getPreviousUri('budgets.create.uri'));
    }

    /**
     * @param BudgetFormRequest $request
     * @param Budget            $budget
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(BudgetFormRequest $request, Budget $budget)
    {
        $data = $request->getBudgetData();
        $this->repository->update($budget, $data);

        $request->session()->flash('success', strval(trans('firefly.updated_budget', ['name' => $budget->name])));
        $this->repository->cleanupBudgets();
        Preferences::mark();

        if (1 === intval($request->get('return_to_edit'))) {
            // @codeCoverageIgnoreStart
            $request->session()->put('budgets.edit.fromUpdate', true);

            return redirect(route('budgets.edit', [$budget->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        return redirect($this->getPreviousUri('budgets.edit.uri'));
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function updateIncome(Request $request, Carbon $start, Carbon $end)
    {
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $available       = $this->repository->getAvailableBudget($defaultCurrency, $start, $end);
        $available       = round($available, $defaultCurrency->decimal_places);
        $page            = intval($request->get('page'));

        return view('budgets.income', compact('available', 'start', 'end', 'page'));
    }

    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    private function getLimits(Budget $budget, Carbon $start, Carbon $end): Collection
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

    /**
     * @return Collection
     */
    private function getPeriodOverview(): Collection
    {
        $repository = app(JournalRepositoryInterface::class);
        $first      = $repository->first();
        $start      = $first->date ?? new Carbon;
        $range      = Preferences::get('viewRange', '1M')->data;
        $start      = app('navigation')->startOfPeriod($start, $range);
        $end        = app('navigation')->endOfX(new Carbon, $range, null);
        $entries    = new Collection;
        $cache      = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('no-budget-period-entries');

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $dates = app('navigation')->blockPeriods($start, $end, $range);
        foreach ($dates as $date) {
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($date['start'], $date['end'])->withoutBudget()->withOpposingAccount()->setTypes(
                [TransactionType::WITHDRAWAL]
            );
            $set      = $collector->getJournals();
            $sum      = strval($set->sum('transaction_amount') ?? '0');
            $journals = $set->count();
            $dateStr  = $date['end']->format('Y-m-d');
            $dateName = app('navigation')->periodShow($date['end'], $date['period']);
            $entries->push(['string' => $dateStr, 'name' => $dateName, 'count' => $journals, 'sum' => $sum, 'date' => clone $date['end']]);
        }
        $cache->store($entries);

        return $entries;
    }
}
