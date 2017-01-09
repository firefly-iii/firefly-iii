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
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Navigation;
use Preferences;
use Session;
use URL;
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
     * @return View
     */
    public function create()
    {
        if (session('categories.create.fromStore') !== true) {
            Session::put('categories.create.url', URL::previous());
        }
        Session::forget('categories.create.fromStore');
        Session::flash('gaEventCategory', 'categories');
        Session::flash('gaEventAction', 'create');
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
        Session::put('categories.delete.url', URL::previous());
        Session::flash('gaEventCategory', 'categories');
        Session::flash('gaEventAction', 'delete');

        return view('categories.delete', compact('category', 'subTitle'));
    }


    /**
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(CategoryRepositoryInterface $repository, Category $category)
    {

        $name       = $category->name;
        $categoryId = $category->id;
        $repository->destroy($category);

        Session::flash('success', strval(trans('firefly.deleted_category', ['name' => e($name)])));
        Preferences::mark();

        $uri = session('categories.delete.url');
        if (!(strpos($uri, sprintf('categories/show/%s', $categoryId)) === false)) {
            // uri would point back to category
            $uri = route('categories.index');
        }

        return redirect($uri);
    }

    /**
     * @param Category $category
     *
     * @return View
     */
    public function edit(Category $category)
    {
        $subTitle = trans('firefly.edit_category', ['name' => $category->name]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('categories.edit.fromUpdate') !== true) {
            Session::put('categories.edit.url', URL::previous());
        }
        Session::forget('categories.edit.fromUpdate');
        Session::flash('gaEventCategory', 'categories');
        Session::flash('gaEventAction', 'edit');

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
    public function noCategory()
    {
        /** @var Carbon $start */
        $start = session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end = session('end', Carbon::now()->startOfMonth());

        // new collector:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class, [auth()->user()]);
        $collector->setAllAssetAccounts()->setRange($start, $end)->withoutCategory();//->groupJournals();
        $journals = $collector->getJournals();
        $subTitle = trans(
            'firefly.without_category_between',
            ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
        );

        return view('categories.no-category', compact('journals', 'subTitle'));
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

        Session::flash('success', strval(trans('firefly.stored_category', ['name' => e($category->name)])));
        Preferences::mark();

        if (intval($request->get('create_another')) === 1) {
            Session::put('categories.create.fromStore', true);

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

        Session::flash('success', strval(trans('firefly.updated_category', ['name' => e($category->name)])));
        Preferences::mark();

        if (intval($request->get('return_to_edit')) === 1) {
            Session::put('categories.edit.fromUpdate', true);

            return redirect(route('categories.edit', [$category->id]));
        }

        // redirect to previous URL.
        return redirect(session('categories.edit.url'));

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
            return $cache->get();
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

}
