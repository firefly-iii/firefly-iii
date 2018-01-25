<?php
/**
 * CategoryController.php
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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use Preferences;
use Steam;
use View;

/**
 * Class CategoryController.
 */
class CategoryController extends Controller
{
    /** @var AccountRepositoryInterface */
    private $accountRepos;
    /** @var JournalRepositoryInterface */
    private $journalRepos;
    /** @var CategoryRepositoryInterface */
    private $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', trans('firefly.categories'));
                app('view')->share('mainTitleIcon', 'fa-bar-chart');
                $this->journalRepos = app(JournalRepositoryInterface::class);
                $this->repository   = app(CategoryRepositoryInterface::class);
                $this->accountRepos = app(AccountRepositoryInterface::class);

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
        if (true !== session('categories.create.fromStore')) {
            $this->rememberPreviousUri('categories.create.uri');
        }
        $request->session()->forget('categories.create.fromStore');
        $subTitle = trans('firefly.create_new_category');

        return view('categories.create', compact('subTitle'));
    }

    /**
     * @param Category $category
     *
     * @return View
     */
    public function delete(Category $category)
    {
        $subTitle = trans('firefly.delete_category', ['name' => $category->name]);

        // put previous url in session
        $this->rememberPreviousUri('categories.delete.uri');

        return view('categories.delete', compact('category', 'subTitle'));
    }

    /**
     * @param Request  $request
     * @param Category $category
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, Category $category)
    {
        $name = $category->name;
        $this->repository->destroy($category);

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
        if (true !== session('categories.edit.fromUpdate')) {
            $this->rememberPreviousUri('categories.edit.uri');
        }
        $request->session()->forget('categories.edit.fromUpdate');

        return view('categories.edit', compact('category', 'subTitle'));
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $page       = 0 === intval($request->get('page')) ? 1 : intval($request->get('page'));
        $pageSize   = intval(Preferences::get('listPageSize', 50)->data);
        $collection = $this->repository->getCategories();
        $total      = $collection->count();
        $collection = $collection->slice(($page - 1) * $pageSize, $pageSize);

        $collection->each(
            function (Category $category) {
                $category->lastActivity = $this->repository->lastUseDate($category, new Collection);
            }
        );

        // paginate categories
        $categories = new LengthAwarePaginator($collection, $total, $pageSize, $page);
        $categories->setPath(route('categories.index'));

        return view('categories.index', compact('categories'));
    }

    /**
     * @param Request $request
     * @param string  $moment
     *
     * @return View
     */
    public function noCategory(Request $request, string $moment = '')
    {
        // default values:
        $range    = Preferences::get('viewRange', '1M')->data;
        $start    = null;
        $end      = null;
        $periods  = new Collection;
        $page     = intval($request->get('page'));
        $pageSize = intval(Preferences::get('listPageSize', 50)->data);

        // prep for "all" view.
        if ('all' === $moment) {
            $subTitle = trans('firefly.all_journals_without_category');
            $first    = $this->journalRepos->first();
            $start    = $first->date ?? new Carbon;
            $end      = new Carbon;
        }

        // prep for "specific date" view.
        if (strlen($moment) > 0 && 'all' !== $moment) {
            $start    = app('navigation')->startOfPeriod(new Carbon($moment), $range);
            $end      = app('navigation')->endOfPeriod($start, $range);
            $subTitle = trans(
                'firefly.without_category_between',
                ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
            );
            $periods  = $this->getNoCategoryPeriodOverview($start);
        }

        // prep for current period
        if (0 === strlen($moment)) {
            $start    = clone session('start', app('navigation')->startOfPeriod(new Carbon, $range));
            $end      = clone session('end', app('navigation')->endOfPeriod(new Carbon, $range));
            $periods  = $this->getNoCategoryPeriodOverview($start);
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
        $transactions = $collector->getPaginatedJournals();
        $transactions->setPath(route('categories.no-category'));

        return view('categories.no-category', compact('transactions', 'subTitle', 'moment', 'periods', 'start', 'end'));
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
        $pageSize     = intval(Preferences::get('listPageSize', 50)->data);
        $range        = Preferences::get('viewRange', '1M')->data;
        $start        = null;
        $end          = null;
        $periods      = new Collection;
        $path         = route('categories.show', [$category->id]);

        // prep for "all" view.
        if ('all' === $moment) {
            $subTitle = trans('firefly.all_journals_for_category', ['name' => $category->name]);
            $first    = $repository->firstUseDate($category);
            /** @var Carbon $start */
            $start = null === $first ? new Carbon : $first;
            $end   = new Carbon;
            $path  = route('categories.show', [$category->id, 'all']);
        }

        // prep for "specific date" view.
        if (strlen($moment) > 0 && 'all' !== $moment) {
            $start    = app('navigation')->startOfPeriod(new Carbon($moment), $range);
            $end      = app('navigation')->endOfPeriod($start, $range);
            $subTitle = trans(
                'firefly.journals_in_period_for_category',
                ['name'  => $category->name,
                 'start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat),]
            );
            $periods  = $this->getPeriodOverview($category, $start);
            $path     = route('categories.show', [$category->id, $moment]);
        }

        // prep for current period
        if (0 === strlen($moment)) {
            /** @var Carbon $start */
            $start = clone session('start', app('navigation')->startOfPeriod(new Carbon, $range));
            /** @var Carbon $end */
            $end      = clone session('end', app('navigation')->endOfPeriod(new Carbon, $range));
            $periods  = $this->getPeriodOverview($category, $start);
            $subTitle = trans(
                'firefly.journals_in_period_for_category',
                ['name' => $category->name, 'start' => $start->formatLocalized($this->monthAndDayFormat),
                 'end'  => $end->formatLocalized($this->monthAndDayFormat),]
            );
        }

        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withOpposingAccount()
                  ->setCategory($category)->withBudgetInformation()->withCategoryInformation();
        $collector->removeFilter(InternalTransferFilter::class);
        $transactions = $collector->getPaginatedJournals();
        $transactions->setPath($path);

        return view('categories.show', compact('category', 'moment', 'transactions', 'periods', 'subTitle', 'subTitleIcon', 'start', 'end'));
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

        if (1 === intval($request->get('create_another'))) {
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

        if (1 === intval($request->get('return_to_edit'))) {
            // @codeCoverageIgnoreStart
            $request->session()->put('categories.edit.fromUpdate', true);

            return redirect(route('categories.edit', [$category->id]));
            // @codeCoverageIgnoreEnd
        }

        return redirect($this->getPreviousUri('categories.edit.uri'));
    }

    /**
     * @param Carbon $theDate
     *
     * @return Collection
     */
    private function getNoCategoryPeriodOverview(Carbon $theDate): Collection
    {
        $range = Preferences::get('viewRange', '1M')->data;
        $first = $this->journalRepos->first();
        $start = $first->date ?? new Carbon;
        $end   = $theDate ?? new Carbon;

        // properties for cache
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('no-category-period-entries');

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $dates   = app('navigation')->blockPeriods($start, $end, $range);
        $entries = new Collection;

        foreach ($dates as $date) {

            // count journals without category in this period:
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($date['start'], $date['end'])->withoutCategory()
                      ->withOpposingAccount()->setTypes([TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER]);
            $collector->removeFilter(InternalTransferFilter::class);
            $count = $collector->getJournals()->count();

            // amount transferred
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($date['start'], $date['end'])->withoutCategory()
                      ->withOpposingAccount()->setTypes([TransactionType::TRANSFER]);
            $collector->removeFilter(InternalTransferFilter::class);
            $transferred = Steam::positive($collector->getJournals()->sum('transaction_amount'));

            // amount spent
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($date['start'], $date['end'])->withoutCategory()->withOpposingAccount()->setTypes(
                [TransactionType::WITHDRAWAL]
            );
            $spent = $collector->getJournals()->sum('transaction_amount');

            // amount earned
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($date['start'], $date['end'])->withoutCategory()->withOpposingAccount()->setTypes(
                [TransactionType::DEPOSIT]
            );
            $earned   = $collector->getJournals()->sum('transaction_amount');
            $dateStr  = $date['end']->format('Y-m-d');
            $dateName = app('navigation')->periodShow($date['end'], $date['period']);
            $entries->push(
                [
                    'string'      => $dateStr,
                    'name'        => $dateName,
                    'count'       => $count,
                    'spent'       => $spent,
                    'earned'      => $earned,
                    'transferred' => $transferred,
                    'date'        => clone $date['end'],
                ]
            );
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
    private function getPeriodOverview(Category $category, Carbon $date): Collection
    {
        $range    = Preferences::get('viewRange', '1M')->data;
        $first    = $this->journalRepos->first();
        $start    = $first->date ?? new Carbon;
        $end      = $date ?? new Carbon;
        $accounts = $this->accountRepos->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);

        // properties for entries with their amounts.
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($range);
        $cache->addProperty('categories.entries');
        $cache->addProperty($category->id);

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $dates   = app('navigation')->blockPeriods($start, $end, $range);
        $entries = new Collection;

        foreach ($dates as $date) {
            $spent    = $this->repository->spentInPeriod(new Collection([$category]), $accounts, $date['start'], $date['end']);
            $earned   = $this->repository->earnedInPeriod(new Collection([$category]), $accounts, $date['start'], $date['end']);
            $dateStr  = $date['end']->format('Y-m-d');
            $dateName = app('navigation')->periodShow($date['end'], $date['period']);

            // amount transferred
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($date['start'], $date['end'])->setCategory($category)
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
                    'date'        => clone $date['end'],
                ]
            );
        }
        $cache->store($entries);

        return $entries;
    }
}
