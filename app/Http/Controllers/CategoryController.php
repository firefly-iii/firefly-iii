<?php
/**
 * CategoryController.php
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
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Http\Requests\CategoryFormRequest;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Log;
use Navigation;
use Preferences;
use Steam;
use View;

/**
 * Class CategoryController
 *
 * @package FireflyIII\Http\Controllers
 */
class CategoryController extends Controller
{

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();


        $this->middleware(
            function ($request, $next) {
                View::share('title', trans('firefly.categories'));
                View::share('mainTitleIcon', 'fa-bar-chart');

                return $next($request);
            }
        );
    }

    /**
     * @param Request $request
     *
     * @return View
     */
    public function create(Request $request)
    {
        if (session('categories.create.fromStore') !== true) {
            $this->rememberPreviousUri('categories.create.uri');
        }
        $request->session()->forget('categories.create.fromStore');
        $request->session()->flash('gaEventCategory', 'categories');
        $request->session()->flash('gaEventAction', 'create');
        $subTitle = trans('firefly.create_new_category');

        return view('categories.create', compact('subTitle'));
    }

    /**
     * @param Request  $request
     * @param Category $category
     *
     * @return View
     */
    public function delete(Request $request, Category $category)
    {
        $subTitle = trans('firefly.delete_category', ['name' => $category->name]);

        // put previous url in session
        $this->rememberPreviousUri('categories.delete.uri');
        $request->session()->flash('gaEventCategory', 'categories');
        $request->session()->flash('gaEventAction', 'delete');

        return view('categories.delete', compact('category', 'subTitle'));
    }


    /**
     * @param Request                     $request
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, CategoryRepositoryInterface $repository, Category $category)
    {

        $name = $category->name;
        $repository->destroy($category);

        $request->session()->flash('success', strval(trans('firefly.deleted_category', ['name' => $name])));
        Preferences::mark();

        return redirect($this->getPreviousUri('categories.delete.uri'));
    }

    /**
     * @param Request  $request
     * @param Category $category
     *
     * @return View
     */
    public function edit(Request $request, Category $category)
    {
        $subTitle = trans('firefly.edit_category', ['name' => $category->name]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('categories.edit.fromUpdate') !== true) {
            $this->rememberPreviousUri('categories.edit.uri');
        }
        $request->session()->forget('categories.edit.fromUpdate');
        $request->session()->flash('gaEventCategory', 'categories');
        $request->session()->flash('gaEventAction', 'edit');

        return view('categories.edit', compact('category', 'subTitle'));

    }

    /**
     * @param CategoryRepositoryInterface $repository
     *
     * @return View
     */
    public function index(CategoryRepositoryInterface $repository)
    {
        $categories = $repository->getCategories();

        $categories->each(
            function (Category $category) use ($repository) {
                $category->lastActivity = $repository->lastUseDate($category, new Collection);
            }
        );

        return view('categories.index', compact('categories'));
    }

    /**
     * @param Request                    $request
     * @param JournalRepositoryInterface $repository
     * @param string                     $moment
     *
     * @return View
     */
    public function noCategory(Request $request, JournalRepositoryInterface $repository, string $moment = '')
    {
        // default values:
        $range    = Preferences::get('viewRange', '1M')->data;
        $start    = null;
        $end      = null;
        $periods  = new Collection;
        $page     = intval($request->get('page'));
        $pageSize = intval(Preferences::get('transactionPageSize', 50)->data);

        // prep for "all" view.
        if ($moment === 'all') {
            $subTitle = trans('firefly.all_journals_without_category');
            $first    = $repository->first();
            $start    = $first->date ?? new Carbon;
            $end      = new Carbon;
        }

        // prep for "specific date" view.
        if (strlen($moment) > 0 && $moment !== 'all') {
            $start    = new Carbon($moment);
            $end      = Navigation::endOfPeriod($start, $range);
            $subTitle = trans(
                'firefly.without_category_between',
                ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
            );
            $periods  = $this->getNoCategoryPeriodOverview();
        }

        // prep for current period
        if (strlen($moment) === 0) {
            $start    = clone session('start', Navigation::startOfPeriod(new Carbon, $range));
            $end      = clone session('end', Navigation::endOfPeriod(new Carbon, $range));
            $periods  = $this->getNoCategoryPeriodOverview();
            $subTitle = trans(
                'firefly.without_category_between',
                ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
            );
        }


        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withoutCategory()->withOpposingAccount()
                  ->setTypes([TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER]);
        $collector->removeFilter(InternalTransferFilter::class);
        $journals = $collector->getPaginatedJournals();
        $journals->setPath(route('categories.no-category'));

        return view('categories.no-category', compact('journals', 'subTitle', 'moment', 'periods', 'start', 'end'));
    }

    /**
     * @param Request                     $request
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     * @param string                      $moment
     *
     * @return View
     */
    public function show(Request $request, CategoryRepositoryInterface $repository, Category $category, string $moment = '')
    {
        // default values:
        $subTitle     = $category->name;
        $subTitleIcon = 'fa-bar-chart';
        $page         = intval($request->get('page'));
        $pageSize     = intval(Preferences::get('transactionPageSize', 50)->data);
        $range        = Preferences::get('viewRange', '1M')->data;
        $start        = null;
        $end          = null;
        $periods      = new Collection;

        // prep for "all" view.
        if ($moment === 'all') {
            $subTitle = trans('firefly.all_journals_for_category', ['name' => $category->name]);
            $first    = $repository->firstUseDate($category);
            /** @var Carbon $start */
            $start = is_null($first) ? new Carbon : $first;
            $end   = new Carbon;
        }

        // prep for "specific date" view.
        if (strlen($moment) > 0 && $moment !== 'all') {
            $start    = new Carbon($moment);
            $end      = Navigation::endOfPeriod($start, $range);
            $subTitle = trans(
                'firefly.journals_in_period_for_category',
                ['name'  => $category->name,
                 'start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
            );
            $periods  = $this->getPeriodOverview($category);
        }

        // prep for current period
        if (strlen($moment) === 0) {
            /** @var Carbon $start */
            $start = clone session('start', Navigation::startOfPeriod(new Carbon, $range));
            /** @var Carbon $end */
            $end      = clone session('end', Navigation::endOfPeriod(new Carbon, $range));
            $periods  = $this->getPeriodOverview($category);
            $subTitle = trans(
                'firefly.journals_in_period_for_category',
                ['name' => $category->name, 'start' => $start->formatLocalized($this->monthAndDayFormat),
                 'end'  => $end->formatLocalized($this->monthAndDayFormat)]
            );
        }

        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withOpposingAccount()
                  ->setCategory($category)->withBudgetInformation()->withCategoryInformation();
        $collector->removeFilter(InternalTransferFilter::class);
        $journals = $collector->getPaginatedJournals();
        $journals->setPath(route('categories.show', [$category->id]));


        return view('categories.show', compact('category', 'moment', 'journals', 'periods', 'subTitle', 'subTitleIcon', 'start', 'end'));
    }


    /**
     * @param CategoryFormRequest         $request
     * @param CategoryRepositoryInterface $repository
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(CategoryFormRequest $request, CategoryRepositoryInterface $repository)
    {
        $data     = $request->getCategoryData();
        $category = $repository->store($data);

        $request->session()->flash('success', strval(trans('firefly.stored_category', ['name' => $category->name])));
        Preferences::mark();

        if (intval($request->get('create_another')) === 1) {
            // @codeCoverageIgnoreStart
            $request->session()->put('categories.create.fromStore', true);

            return redirect(route('categories.create'))->withInput();
            // @codeCoverageIgnoreEnd
        }

        return redirect(route('categories.index'));
    }


    /**
     * @param CategoryFormRequest         $request
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(CategoryFormRequest $request, CategoryRepositoryInterface $repository, Category $category)
    {
        $data = $request->getCategoryData();
        $repository->update($category, $data);

        $request->session()->flash('success', strval(trans('firefly.updated_category', ['name' => $category->name])));
        Preferences::mark();

        if (intval($request->get('return_to_edit')) === 1) {
            // @codeCoverageIgnoreStart
            $request->session()->put('categories.edit.fromUpdate', true);

            return redirect(route('categories.edit', [$category->id]));
            // @codeCoverageIgnoreEnd
        }

        return redirect($this->getPreviousUri('categories.edit.uri'));
    }

    /**
     * @return Collection
     */
    private function getNoCategoryPeriodOverview(): Collection
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

        Log::debug(sprintf('Going to get period expenses and incomes between %s and %s.', $start->format('Y-m-d'), $end->format('Y-m-d')));
        while ($end >= $start) {
            Log::debug('Loop!');
            $end        = Navigation::startOfPeriod($end, $range);
            $currentEnd = Navigation::endOfPeriod($end, $range);

            // count journals without category in this period:
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($end, $currentEnd)->withoutCategory()
                      ->withOpposingAccount()->setTypes([TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER]);
            $collector->removeFilter(InternalTransferFilter::class);
            $count = $collector->getJournals()->count();

            // amount transferred
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($end, $currentEnd)->withoutCategory()
                      ->withOpposingAccount()->setTypes([TransactionType::TRANSFER]);
            $collector->removeFilter(InternalTransferFilter::class);
            $transferred = Steam::positive($collector->getJournals()->sum('transaction_amount'));

            // amount spent
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($end, $currentEnd)->withoutCategory()->withOpposingAccount()->setTypes([TransactionType::WITHDRAWAL]);
            $spent = $collector->getJournals()->sum('transaction_amount');

            // amount earned
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($end, $currentEnd)->withoutCategory()->withOpposingAccount()->setTypes([TransactionType::DEPOSIT]);
            $earned = $collector->getJournals()->sum('transaction_amount');

            $dateStr  = $end->format('Y-m-d');
            $dateName = Navigation::periodShow($end, $range);
            $entries->push(
                [
                    'string'      => $dateStr,
                    'name'        => $dateName,
                    'count'       => $count,
                    'spent'       => $spent,
                    'earned'      => $earned,
                    'transferred' => $transferred,
                    'date'        => clone $end,
                ]
            );
            $end = Navigation::subtractPeriod($end, $range, 1);
        }
        Log::debug('End of loops');
        $cache->store($entries);

        return $entries;
    }

    /**
     * @param Category $category
     *
     * @return Collection
     */
    private function getPeriodOverview(Category $category): Collection
    {
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accounts          = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $first             = $repository->firstUseDate($category);
        if (is_null($first)) {
            $first = new Carbon;
        }
        $range   = Preferences::get('viewRange', '1M')->data;
        $first   = Navigation::startOfPeriod($first, $range);
        $end     = Navigation::endOfX(new Carbon, $range, null);
        $entries = new Collection;
        $count   = 0;

        // properties for entries with their amounts.
        $cache = new CacheProperties();
        $cache->addProperty($first);
        $cache->addProperty($end);
        $cache->addProperty('categories.entries');
        $cache->addProperty($category->id);

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        while ($end >= $first && $count < 90) {
            $end        = Navigation::startOfPeriod($end, $range);
            $currentEnd = Navigation::endOfPeriod($end, $range);
            $spent      = $repository->spentInPeriod(new Collection([$category]), $accounts, $end, $currentEnd);
            $earned     = $repository->earnedInPeriod(new Collection([$category]), $accounts, $end, $currentEnd);
            $dateStr    = $end->format('Y-m-d');
            $dateName   = Navigation::periodShow($end, $range);

            // amount transferred
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($end, $currentEnd)->setCategory($category)
                      ->withOpposingAccount()->setTypes([TransactionType::TRANSFER]);
            $collector->removeFilter(InternalTransferFilter::class);
            $transferred = Steam::positive($collector->getJournals()->sum('transaction_amount'));

            $entries->push(
                [
                    'string'      => $dateStr,
                    'name'        => $dateName,
                    'spent'       => $spent,
                    'earned'      => $earned,
                    'sum'         => bcadd($earned, $spent),
                    'transferred' => $transferred,
                    'date'        => clone $end,
                ]
            );
            $end = Navigation::subtractPeriod($end, $range, 1);
            $count++;
        }
        $cache->store($entries);

        return $entries;
    }

}
