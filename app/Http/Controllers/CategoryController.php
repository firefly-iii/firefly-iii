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
use FireflyIII\Http\Requests\CategoryFormRequest;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface as CRI;
use FireflyIII\Support\CacheProperties;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Input;
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
        // put previous url in session if not redirect from store (not "create another").
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
     * @param CRI      $repository
     * @param Category $category
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(CRI $repository, Category $category)
    {

        $name = $category->name;
        $repository->destroy($category);

        Session::flash('success', strval(trans('firefly.deleted_category', ['name' => e($name)])));
        Preferences::mark();

        return redirect(session('categories.delete.url'));
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
     * @param CRI $repository
     *
     * @return View
     */
    public function index(CRI $repository)
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
     * @param CRI $repository
     *
     * @return View
     */
    public function noCategory(CRI $repository)
    {
        /** @var Carbon $start */
        $start = session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end      = session('end', Carbon::now()->startOfMonth());
        $list     = $repository->journalsInPeriodWithoutCategory(new Collection(), [], $start, $end); // category
        $subTitle = trans(
            'firefly.without_category_between',
            ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
        );

        return view('categories.noCategory', compact('list', 'subTitle'));
    }

    /**
     * @param CRI                        $repository
     * @param AccountRepositoryInterface $accountRepository
     * @param Category                   $category
     *
     * @return View
     */
    public function show(CRI $repository, AccountRepositoryInterface $accountRepository, Category $category)
    {
        $range = Preferences::get('viewRange', '1M')->data;
        /** @var Carbon $start */
        $start = session('start', Navigation::startOfPeriod(new Carbon, $range));
        /** @var Carbon $end */
        $end          = session('end', Navigation::endOfPeriod(new Carbon, $range));
        $hideCategory = true; // used in list.
        $page         = intval(Input::get('page'));
        $pageSize     = Preferences::get('transactionPageSize', 50)->data;
        $offset       = ($page - 1) * $pageSize;
        $set          = $repository->journalsInPeriod(new Collection([$category]), new Collection, [], $start, $end); // category
        $count        = $set->count();
        $subSet       = $set->splice($offset, $pageSize);
        $subTitle     = $category->name;
        $subTitleIcon = 'fa-bar-chart';
        $journals     = new LengthAwarePaginator($subSet, $count, $pageSize, $page);
        $journals->setPath('categories/show/' . $category->id);

        // oldest transaction in category:
        $start = $repository->firstUseDate($category);
        if ($start->year == 1900) {
            $start = new Carbon;
        }
        $range   = Preferences::get('viewRange', '1M')->data;
        $start   = Navigation::startOfPeriod($start, $range);
        $end     = Navigation::endOfX(new Carbon, $range);
        $entries = new Collection;

        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('category-show');
        $cache->addProperty($category->id);


        if ($cache->has()) {
            $entries = $cache->get();

            return view('categories.show', compact('category', 'journals', 'entries', 'subTitleIcon', 'hideCategory', 'subTitle'));
        }


        $categoryCollection = new Collection([$category]);
        $accounts           = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        while ($end >= $start) {
            $end        = Navigation::startOfPeriod($end, $range);
            $currentEnd = Navigation::endOfPeriod($end, $range);
            $spent      = $repository->spentInPeriod($categoryCollection, $accounts, $end, $currentEnd);
            $earned     = $repository->earnedInPeriod($categoryCollection, $accounts, $end, $currentEnd);
            $dateStr    = $end->format('Y-m-d');
            $dateName   = Navigation::periodShow($end, $range);
            $entries->push([$dateStr, $dateName, $spent, $earned]);

            $end = Navigation::subtractPeriod($end, $range, 1);

        }
        $cache->store($entries);

        return view('categories.show', compact('category', 'journals', 'entries', 'hideCategory', 'subTitle'));
    }

    /**
     * @param CRI                               $repository
     * @param Category                          $category
     *
     * @param                                   $date
     *
     * @return View
     */
    public function showWithDate(CRI $repository, Category $category, string $date)
    {
        $carbon       = new Carbon($date);
        $range        = Preferences::get('viewRange', '1M')->data;
        $start        = Navigation::startOfPeriod($carbon, $range);
        $end          = Navigation::endOfPeriod($carbon, $range);
        $subTitle     = $category->name;
        $hideCategory = true; // used in list.
        $page         = intval(Input::get('page'));
        $pageSize     = Preferences::get('transactionPageSize', 50)->data;
        $offset       = ($page - 1) * $pageSize;
        $set          = $repository->journalsInPeriod(new Collection([$category]), new Collection, [], $start, $end); // category
        $count        = $set->count();
        $subSet       = $set->splice($offset, $pageSize);
        $journals     = new LengthAwarePaginator($subSet, $count, $pageSize, $page);
        $journals->setPath('categories/show/' . $category->id . '/' . $date);

        return view('categories.show_with_date', compact('category', 'journals', 'hideCategory', 'subTitle', 'carbon'));
    }

    /**
     * @param CategoryFormRequest $request
     * @param CRI                 $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(CategoryFormRequest $request, CRI $repository)
    {
        $data     = $request->getCategoryData();
        $category = $repository->store($data);

        Session::flash('success', strval(trans('firefly.stored_category', ['name' => e($category->name)])));
        Preferences::mark();

        if (intval(Input::get('create_another')) === 1) {
            Session::put('categories.create.fromStore', true);

            return redirect(route('categories.create'))->withInput();
        }

        return redirect(route('categories.index'));
    }


    /**
     * @param CategoryFormRequest $request
     * @param CRI                 $repository
     * @param Category            $category
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(CategoryFormRequest $request, CRI $repository, Category $category)
    {
        $data = $request->getCategoryData();
        $repository->update($category, $data);

        Session::flash('success', strval(trans('firefly.updated_category', ['name' => e($category->name)])));
        Preferences::mark();

        if (intval(Input::get('return_to_edit')) === 1) {
            Session::put('categories.edit.fromUpdate', true);

            return redirect(route('categories.edit', [$category->id]));
        }

        // redirect to previous URL.
        return redirect(session('categories.edit.url'));

    }

}
