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

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
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

        $request->session()->flash('success', strval(trans('firefly.deleted_category', ['name' => e($name)])));
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
     * @return View
     */
    public function noCategory(Request $request, JournalRepositoryInterface $repository, string $moment = '')
    {
        // default values:
        $range   = Preferences::get('viewRange', '1M')->data;
        $start   = null;
        $end     = null;
        $periods = new Collection;

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
            $periods  = $this->noCategoryPeriodEntries();
        }

        // prep for current period
        if (strlen($moment) === 0) {
            $start    = clone session('start', Navigation::startOfPeriod(new Carbon, $range));
            $end      = clone session('end', Navigation::endOfPeriod(new Carbon, $range));
            $periods  = $this->noCategoryPeriodEntries();
            $subTitle = trans(
                'firefly.without_category_between',
                ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
            );
        }

        $page     = intval($request->get('page')) == 0 ? 1 : intval($request->get('page'));
        $pageSize = intval(Preferences::get('transactionPageSize', 50)->data);

        $count = 0;
        $loop  = 0;
        // grab journals, but be prepared to jump a period back to get the right ones:
        Log::info('Now at no-cat loop start.');
        while ($count === 0 && $loop < 3) {
            $loop++;
            Log::info('Count is zero, search for journals.');
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withoutCategory()->withOpposingAccount();
            $collector->disableInternalFilter();
            $journals = $collector->getPaginatedJournals();
            $journals->setPath('/categories/list/no-category');
            $count = $journals->getCollection()->count();
            if ($count === 0) {
                $start->subDay();
                $start = Navigation::startOfPeriod($start, $range);
                $end   = Navigation::endOfPeriod($start, $range);
                Log::info(sprintf('Count is still zero, go back in time to "%s" and "%s"!', $start->format('Y-m-d'), $end->format('Y-m-d')));
            }
        }

        // fix title:
        if ((strlen($moment) > 0 && $moment !== 'all') || strlen($moment) === 0) {
            $subTitle = trans(
                'firefly.without_category_between',
                ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
            );
        }

        return view('categories.no-category', compact('journals', 'subTitle', 'moment', 'periods', 'start', 'end'));
    }

    /**
     * @param Request                   $request
     * @param JournalCollectorInterface $collector
     * @param Category                  $category
     *
     * @return View
     */
    public function show(Request $request, JournalCollectorInterface $collector, Category $category)
    {
        $range        = Preferences::get('viewRange', '1M')->data;
        $start        = session('start', Navigation::startOfPeriod(new Carbon, $range));
        $end          = session('end', Navigation::endOfPeriod(new Carbon, $range));
        $hideCategory = true; // used in list.
        $page         = intval($request->get('page')) === 0 ? 1 : intval($request->get('page'));
        $pageSize     = intval(Preferences::get('transactionPageSize', 50)->data);
        $subTitle     = $category->name;
        $subTitleIcon = 'fa-bar-chart';
        $entries      = $this->getGroupedEntries($category);
        $method       = 'default';

        // get journals
        $collector->setLimit($pageSize)->setPage($page)->setAllAssetAccounts()->setRange($start, $end)->setCategory($category)->withBudgetInformation();
        $journals = $collector->getPaginatedJournals();
        $journals->setPath('categories/show/' . $category->id);


        return view('categories.show', compact('category', 'method', 'journals', 'entries', 'hideCategory', 'subTitle', 'subTitleIcon', 'start', 'end'));
    }

    /**
     * @param Request                     $request
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     *
     * @return View
     */
    public function showAll(Request $request, CategoryRepositoryInterface $repository, Category $category)
    {
        $range = Preferences::get('viewRange', '1M')->data;
        $start = $repository->firstUseDate($category);
        if ($start->year == 1900) {
            $start = new Carbon;
        }
        $end          = Navigation::endOfPeriod(new Carbon, $range);
        $subTitle     = $category->name;
        $subTitleIcon = 'fa-bar-chart';
        $hideCategory = true; // used in list.
        $page         = intval($request->get('page')) === 0 ? 1 : intval($request->get('page'));
        $pageSize     = intval(Preferences::get('transactionPageSize', 50)->data);
        $method       = 'all';

        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setLimit($pageSize)->setPage($page)->setAllAssetAccounts()->setCategory($category)->withBudgetInformation();
        $journals = $collector->getPaginatedJournals();
        $journals->setPath('categories/show/' . $category->id . '/all');

        return view('categories.show', compact('category', 'method', 'journals', 'hideCategory', 'subTitle', 'subTitleIcon', 'start', 'end'));
    }

    /**
     * @param Request  $request
     * @param Category $category
     * @param string   $date
     *
     * @return View
     */
    public function showByDate(Request $request, Category $category, string $date)
    {
        $carbon       = new Carbon($date);
        $range        = Preferences::get('viewRange', '1M')->data;
        $start        = Navigation::startOfPeriod($carbon, $range);
        $end          = Navigation::endOfPeriod($carbon, $range);
        $subTitle     = $category->name;
        $subTitleIcon = 'fa-bar-chart';
        $hideCategory = true; // used in list.
        $page         = intval($request->get('page')) === 0 ? 1 : intval($request->get('page'));
        $pageSize     = intval(Preferences::get('transactionPageSize', 50)->data);
        $entries      = $this->getGroupedEntries($category);
        $method       = 'date';

        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setLimit($pageSize)->setPage($page)->setAllAssetAccounts()->setRange($start, $end)->setCategory($category)->withBudgetInformation();
        $journals = $collector->getPaginatedJournals();
        $journals->setPath('categories/show/' . $category->id . '/' . $date);

        return view('categories.show', compact('category', 'method', 'entries', 'journals', 'hideCategory', 'subTitle', 'subTitleIcon', 'start', 'end'));
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

        $request->session()->flash('success', strval(trans('firefly.stored_category', ['name' => e($category->name)])));
        Preferences::mark();

        if (intval($request->get('create_another')) === 1) {
            $request->session()->put('categories.create.fromStore', true);

            return redirect(route('categories.create'))->withInput();
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

        $request->session()->flash('success', strval(trans('firefly.updated_category', ['name' => e($category->name)])));
        Preferences::mark();

        if (intval($request->get('return_to_edit')) === 1) {
            $request->session()->put('categories.edit.fromUpdate', true);

            return redirect(route('categories.edit', [$category->id]));
        }

        return redirect($this->getPreviousUri('categories.edit.uri'));
    }

    /**
     * @param Category $category
     *
     * @return Collection
     */
    private function getGroupedEntries(Category $category): Collection
    {
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accounts          = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $first             = $repository->firstUseDate($category);
        if ($first->year == 1900) {
            $first = new Carbon;
        }
        $range   = Preferences::get('viewRange', '1M')->data;
        $first   = Navigation::startOfPeriod($first, $range);
        $end     = Navigation::endOfX(new Carbon, $range);
        $entries = new Collection;

        // properties for entries with their amounts.
        $cache = new CacheProperties();
        $cache->addProperty($first);
        $cache->addProperty($end);
        $cache->addProperty('categories.entries');
        $cache->addProperty($category->id);

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        while ($end >= $first) {
            $end        = Navigation::startOfPeriod($end, $range);
            $currentEnd = Navigation::endOfPeriod($end, $range);
            $spent      = $repository->spentInPeriod(new Collection([$category]), $accounts, $end, $currentEnd);
            $earned     = $repository->earnedInPeriod(new Collection([$category]), $accounts, $end, $currentEnd);
            $dateStr    = $end->format('Y-m-d');
            $dateName   = Navigation::periodShow($end, $range);
            $entries->push([$dateStr, $dateName, $spent, $earned, clone $end]);
            $end = Navigation::subtractPeriod($end, $range, 1);
        }
        $cache->store($entries);

        return $entries;
    }


    /**
     * @return Collection
     */
    private function noCategoryPeriodEntries(): Collection
    {
        $repository = app(JournalRepositoryInterface::class);
        $first      = $repository->first();
        $start      = $first->date ?? new Carbon;
        $range      = Preferences::get('viewRange', '1M')->data;
        $start      = Navigation::startOfPeriod($start, $range);
        $end        = Navigation::endOfX(new Carbon, $range);
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
            $collector->setAllAssetAccounts()->setRange($end, $currentEnd)->withoutCategory()->withOpposingAccount();
            $count = $collector->getJournals()->count();

            // amount transferred
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($end, $currentEnd)->withoutCategory()
                      ->withOpposingAccount()->setTypes([TransactionType::TRANSFER])->disableInternalFilter();
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
        $cache->store($entries);

        return $entries;
    }

}
