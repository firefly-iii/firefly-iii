<?php
/**
 * BudgetController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Requests\BudgetFormRequest;
use FireflyIII\Http\Requests\BudgetIncomeRequest;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Log;
use Navigation;
use Preferences;
use Response;
use View;

/**
 * Class BudgetController
 *
 * @package FireflyIII\Http\Controllers
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BudgetController extends Controller
{

    /** @var  BudgetRepositoryInterface */
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
                View::share('title', trans('firefly.budgets'));
                View::share('mainTitleIcon', 'fa-tasks');
                $this->repository = app(BudgetRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Request $request
     * @param Budget  $budget
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function amount(Request $request, Budget $budget)
    {
        $amount      = intval($request->get('amount'));
        $start       = Carbon::createFromFormat('Y-m-d', $request->get('start'));
        $end         = Carbon::createFromFormat('Y-m-d', $request->get('end'));
        $budgetLimit = $this->repository->updateLimitAmount($budget, $start, $end, $amount);
        if ($amount === 0) {
            $budgetLimit = null;
        }
        Preferences::mark();

        return Response::json(['name' => $budget->name, 'limit' => $budgetLimit ? $budgetLimit->id : 0, 'amount' => $amount]);

    }

    /**
     * @param Request $request
     *
     * @return View
     */
    public function create(Request $request)
    {
        // put previous url in session if not redirect from store (not "create another").
        if (session('budgets.create.fromStore') !== true) {
            $this->rememberPreviousUri('budgets.create.uri');
        }
        $request->session()->forget('budgets.create.fromStore');
        $request->session()->flash('gaEventCategory', 'budgets');
        $request->session()->flash('gaEventAction', 'create');
        $subTitle = (string)trans('firefly.create_new_budget');

        return view('budgets.create', compact('subTitle'));
    }

    /**
     * @param Request $request
     * @param Budget  $budget
     *
     * @return View
     */
    public function delete(Request $request, Budget $budget)
    {
        $subTitle = trans('firefly.delete_budget', ['name' => $budget->name]);

        // put previous url in session
        $this->rememberPreviousUri('budgets.delete.uri');
        $request->session()->flash('gaEventCategory', 'budgets');
        $request->session()->flash('gaEventAction', 'delete');

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
        $request->session()->flash('success', strval(trans('firefly.deleted_budget', ['name' => e($name)])));
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
        if (session('budgets.edit.fromUpdate') !== true) {
            $this->rememberPreviousUri('budgets.edit.uri');
        }
        $request->session()->forget('budgets.edit.fromUpdate');
        $request->session()->flash('gaEventCategory', 'budgets');
        $request->session()->flash('gaEventAction', 'edit');

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
    public function index(string $moment = null)
    {
        $range = Preferences::get('viewRange', '1M')->data;
        $start = session('start', new Carbon);
        $end   = session('end', new Carbon);

        // make date if present:
        if (!is_null($moment) || strlen(strval($moment)) !== 0) {
            try {
                $start = new Carbon($moment);
                $end   = Navigation::endOfPeriod($start, $range);
            } catch (Exception $e) {
                // start and end are already defined.
            }
        }
        $next = clone $end;
        $next->addDay();
        $prev = clone $start;
        $prev->subDay();
        $prev = Navigation::startOfPeriod($prev, $range);
        $this->repository->cleanupBudgets();
        $budgets           = $this->repository->getActiveBudgets();
        $inactive          = $this->repository->getInactiveBudgets();
        $periodStart       = $start->formatLocalized($this->monthAndDayFormat);
        $periodEnd         = $end->formatLocalized($this->monthAndDayFormat);
        $budgetInformation = $this->repository->collectBudgetInformation($budgets, $start, $end);
        $defaultCurrency   = app('amount')->getDefaultCurrency();
        $available         = $this->repository->getAvailableBudget($defaultCurrency, $start, $end);
        $spent             = array_sum(array_column($budgetInformation, 'spent'));
        $budgeted          = array_sum(array_column($budgetInformation, 'budgeted'));

        // select thing for last 12 periods:
        $previousLoop = [];
        $previousDate = clone $start;
        $count        = 0;
        while ($count < 12) {
            $previousDate->subDay();
            $previousDate          = Navigation::startOfPeriod($previousDate, $range);
            $format                = $previousDate->format('Y-m-d');
            $previousLoop[$format] = Navigation::periodShow($previousDate, $range);
            $count++;
        }

        // select thing for next 12 periods:
        $nextLoop = [];
        $nextDate = clone $end;
        $nextDate->addDay();
        $count = 0;

        while ($count < 12) {
            $format            = $nextDate->format('Y-m-d');
            $nextLoop[$format] = Navigation::periodShow($nextDate, $range);
            $nextDate          = Navigation::endOfPeriod($nextDate, $range);
            $count++;
            $nextDate->addDay();
        }

        // display info
        $currentMonth = Navigation::periodShow($start, $range);
        $nextText     = Navigation::periodShow($next, $range);
        $prevText     = Navigation::periodShow($prev, $range);

        return view(
            'budgets.index',
            compact(
                'available', 'currentMonth', 'next', 'nextText', 'prev', 'prevText',
                'periodStart', 'periodEnd', 'budgetInformation', 'inactive', 'budgets',
                'spent', 'budgeted', 'previousLoop', 'nextLoop', 'start', 'end'
            )
        );
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
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

        if ($cache->has()) {
            $result = $cache->get(); // @codeCoverageIgnore
        }
        if (!$cache->has()) {
            $result   = [
                'available' => '0',
                'earned'    => '0',
                'suggested' => '0',
            ];
            $currency = app('amount')->getDefaultCurrency();
            $range    = Preferences::get('viewRange', '1M')->data;
            $begin    = Navigation::subtractPeriod($start, $range, 3);

            // get average amount available.
            $total        = '0';
            $count        = 0;
            $currentStart = clone $begin;
            while ($currentStart < $start) {
                $currentEnd   = Navigation::endOfPeriod($currentStart, $range);
                $total        = bcadd($total, $this->repository->getAvailableBudget($currency, $currentStart, $currentEnd));
                $currentStart = Navigation::addPeriod($currentStart, $range, 0);
                $count++;
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
            $result['suggested'] = bccomp($result['suggested'], $result['earned']) === 1 ? $result['earned'] : $result['suggested'];
            // unless it's more than you earned. So min() of suggested/earned


            $cache->store($result);
        }


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
        if ($moment === 'all') {
            $subTitle = trans('firefly.all_journals_without_budget');
            $first    = $repository->first();
            $start    = $first->date ?? new Carbon;
            $end      = new Carbon;
        }

        // prep for "specific date" view.
        if (strlen($moment) > 0 && $moment !== 'all') {
            $start    = new Carbon($moment);
            $end      = Navigation::endOfPeriod($start, $range);
            $subTitle = trans(
                'firefly.without_budget_between',
                ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
            );
            $periods  = $this->getPeriodOverview();
        }

        // prep for current period
        if (strlen($moment) === 0) {
            $start    = clone session('start', Navigation::startOfPeriod(new Carbon, $range));
            $end      = clone session('end', Navigation::endOfPeriod(new Carbon, $range));
            $periods  = $this->getPeriodOverview();
            $subTitle = trans(
                'firefly.without_budget_between',
                ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
            );
        }

        $page     = intval($request->get('page'));
        $pageSize = intval(Preferences::get('transactionPageSize', 50)->data);

        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setLimit($pageSize)->setPage($page)
                  ->withoutBudget()->withOpposingAccount();
        $journals = $collector->getPaginatedJournals();
        $journals->setPath(route('budgets.no-budget'));

        return view('budgets.no-budget', compact('journals', 'subTitle', 'moment', 'periods', 'start', 'end'));
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

        $this->repository->setAvailableBudget($defaultCurrency, $start, $end, $amount);
        Preferences::mark();

        return redirect(route('budgets.index', [$start->format('Y-m-d')]));
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
        $pageSize   = intval(Preferences::get('transactionPageSize', 50)->data);
        $limits     = $this->getLimits($budget, $start, $end);
        $repetition = null;
        // collector:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setBudget($budget)->setLimit($pageSize)->setPage($page)->withCategoryInformation();
        $journals = $collector->getPaginatedJournals();
        $journals->setPath(route('budgets.show', [$budget->id]));


        $subTitle = trans('firefly.all_journals_for_budget', ['name' => $budget->name]);

        return view('budgets.show', compact('limits', 'budget', 'repetition', 'journals', 'subTitle'));
    }

    /**
     * @param Request     $request
     * @param Budget      $budget
     * @param BudgetLimit $budgetLimit
     *
     * @return View
     * @throws FireflyException
     */
    public function showByBudgetLimit(Request $request, Budget $budget, BudgetLimit $budgetLimit)
    {
        if ($budgetLimit->budget->id !== $budget->id) {
            throw new FireflyException('This budget limit is not part of this budget.');
        }

        $page     = intval($request->get('page'));
        $pageSize = intval(Preferences::get('transactionPageSize', 50)->data);
        $subTitle = trans(
            'firefly.budget_in_period', [
                                          'name'  => $budget->name,
                                          'start' => $budgetLimit->start_date->formatLocalized($this->monthAndDayFormat),
                                          'end'   => $budgetLimit->end_date->formatLocalized($this->monthAndDayFormat),
                                      ]
        );

        // collector:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($budgetLimit->start_date, $budgetLimit->end_date)
                  ->setBudget($budget)->setLimit($pageSize)->setPage($page)->withCategoryInformation();
        $journals = $collector->getPaginatedJournals();
        $journals->setPath(route('budgets.show', [$budget->id, $budgetLimit->id]));


        $start  = session('first', Carbon::create()->startOfYear());
        $end    = new Carbon;
        $limits = $this->getLimits($budget, $start, $end);

        return view('budgets.show', compact('limits', 'budget', 'budgetLimit', 'journals', 'subTitle'));

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

        $request->session()->flash('success', strval(trans('firefly.stored_new_budget', ['name' => e($budget->name)])));
        Preferences::mark();

        if (intval($request->get('create_another')) === 1) {
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

        $request->session()->flash('success', strval(trans('firefly.updated_budget', ['name' => e($budget->name)])));
        Preferences::mark();

        if (intval($request->get('return_to_edit')) === 1) {
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
    public function updateIncome(Carbon $start, Carbon $end)
    {
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $available       = $this->repository->getAvailableBudget($defaultCurrency, $start, $end);
        $available       = round($available, $defaultCurrency->decimal_places);

        return view('budgets.income', compact('available', 'start', 'end'));
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

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accounts          = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET, AccountType::CASH]);
        $set               = $this->repository->getBudgetLimits($budget, $start, $end);
        $limits            = new Collection();

        /** @var BudgetLimit $entry */
        foreach ($set as $entry) {
            $entry->spent = $this->repository->spentInPeriod(new Collection([$budget]), $accounts, $entry->start_date, $entry->end_date);
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
        $start      = Navigation::startOfPeriod($start, $range);
        $end        = Navigation::endOfX(new Carbon, $range, null);
        $entries    = new Collection;

        // properties for cache
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('no-budget-period-entries');

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        Log::debug('Going to get period expenses and incomes.');
        while ($end >= $start) {
            $end        = Navigation::startOfPeriod($end, $range);
            $currentEnd = Navigation::endOfPeriod($end, $range);

            // count journals without budget in this period:
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($end, $currentEnd)->withoutBudget()->withOpposingAccount()->setTypes([TransactionType::WITHDRAWAL]);
            $set      = $collector->getJournals();
            $sum      = $set->sum('transaction_amount');
            $journals = $set->count();
            $dateStr  = $end->format('Y-m-d');
            $dateName = Navigation::periodShow($end, $range);
            $entries->push(
                [
                    'string' => $dateStr,
                    'name'   => $dateName,
                    'count'  => $journals,
                    'sum'    => $sum,
                    'date'   => clone $end,
                ]
            );
            $end = Navigation::subtractPeriod($end, $range, 1);
        }
        $cache->store($entries);

        return $entries;
    }

}
