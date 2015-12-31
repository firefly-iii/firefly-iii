<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use FireflyIII\Http\Requests\CategoryFormRequest;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface as CRI;
use FireflyIII\Repositories\Category\SingleCategoryRepositoryInterface as SCRI;
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
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        View::share('title', trans('firefly.categories'));
        View::share('mainTitleIcon', 'fa-bar-chart');
    }

    /**
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // put previous url in session if not redirect from store (not "create another").
        if (Session::get('categories.create.fromStore') !== true) {
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
     * @return \Illuminate\View\View
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
     * @param SCRI     $repository
     * @param Category $category
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(SCRI $repository, Category $category)
    {

        $name = $category->name;
        $repository->destroy($category);

        Session::flash('success', 'The  category "' . e($name) . '" was deleted.');
        Preferences::mark();

        return redirect(Session::get('categories.delete.url'));
    }

    /**
     * @param Category $category
     *
     * @return \Illuminate\View\View
     */
    public function edit(Category $category)
    {
        $subTitle = trans('firefly.edit_category', ['name' => $category->name]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (Session::get('categories.edit.fromUpdate') !== true) {
            Session::put('categories.edit.url', URL::previous());
        }
        Session::forget('categories.edit.fromUpdate');
        Session::flash('gaEventCategory', 'categories');
        Session::flash('gaEventAction', 'edit');

        return view('categories.edit', compact('category', 'subTitle'));

    }

    /**
     * @param CRI  $repository
     * @param SCRI $singleRepository
     *
     * @return \Illuminate\View\View
     */
    public function index(CRI $repository, SCRI $singleRepository)
    {
        $categories = $repository->listCategories();

        $categories->each(
            function (Category $category) use ($singleRepository) {
                $category->lastActivity = $singleRepository->getLatestActivity($category);
            }
        );

        return view('categories.index', compact('categories'));
    }

    /**
     * @param CRI $repository
     *
     * @return \Illuminate\View\View
     */
    public function noCategory(CRI $repository)
    {
        $start    = Session::get('start', Carbon::now()->startOfMonth());
        $end      = Session::get('end', Carbon::now()->startOfMonth());
        $list     = $repository->listNoCategory($start, $end);
        $subTitle = trans(
            'firefly.without_category_between',
            ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
        );

        return view('categories.noCategory', compact('list', 'subTitle'));
    }

    /**
     * @param SCRI                              $repository
     * @param Category                          $category
     *
     * @param                                   $date
     *
     * @return \Illuminate\View\View
     */
    public function showWithDate(SCRI $repository, Category $category, $date)
    {
        $carbon   = new Carbon($date);
        $range    = Preferences::get('viewRange', '1M')->data;
        $start    = Navigation::startOfPeriod($carbon, $range);
        $end      = Navigation::endOfPeriod($carbon, $range);
        $subTitle = $category->name;

        $hideCategory = true; // used in list.
        $page         = intval(Input::get('page'));

        $set      = $repository->getJournalsInRange($category, $page, $start, $end);
        $count    = $repository->countJournalsInRange($category, $start, $end);
        $journals = new LengthAwarePaginator($set, $count, 50, $page);
        $journals->setPath('categories/show/' . $category->id . '/' . $date);

        return view('categories.show_with_date', compact('category', 'journals', 'hideCategory', 'subTitle', 'carbon'));
    }

    /**
     * @param SCRI     $repository
     * @param Category $category
     *
     * @return \Illuminate\View\View
     */
    public function show(SCRI $repository, Category $category)
    {
        $hideCategory = true; // used in list.
        $page         = intval(Input::get('page'));
        $set          = $repository->getJournals($category, $page);
        $count        = $repository->countJournals($category);
        $subTitle     = $category->name;
        $journals     = new LengthAwarePaginator($set, $count, 50, $page);
        $journals->setPath('categories/show/' . $category->id);

        // list of ranges for list of periods:

        // oldest transaction in category:
        $start   = $repository->getFirstActivityDate($category);
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

        // get all spent and earned data:
        // get amount earned in period, grouped by day.
        $spentArray  = $repository->spentPerDay($category, $start, $end);
        $earnedArray = $repository->earnedPerDay($category, $start, $end);

        if ($cache->has()) {
            $entries = $cache->get();
        } else {

            while ($end >= $start) {
                $end        = Navigation::startOfPeriod($end, $range);
                $currentEnd = Navigation::endOfPeriod($end, $range);

                // get data from spentArray:
                $spent    = $this->getSumOfRange($end, $currentEnd, $spentArray);
                $earned   = $this->getSumOfRange($end, $currentEnd, $earnedArray);
                $dateStr  = $end->format('Y-m-d');
                $dateName = Navigation::periodShow($end, $range);
                $entries->push([$dateStr, $dateName, $spent, $earned]);

                $end = Navigation::subtractPeriod($end, $range, 1);

            }
            $cache->store($entries);
        }

        return view('categories.show', compact('category', 'journals', 'entries', 'hideCategory', 'subTitle'));
    }

    /**
     * @param CategoryFormRequest $request
     * @param SCRI                $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(CategoryFormRequest $request, SCRI $repository)
    {
        $categoryData = [
            'name' => $request->input('name'),
            'user' => Auth::user()->id,
        ];
        $category     = $repository->store($categoryData);

        Session::flash('success', 'New category "' . $category->name . '" stored!');
        Preferences::mark();

        if (intval(Input::get('create_another')) === 1) {
            Session::put('categories.create.fromStore', true);

            return redirect(route('categories.create'))->withInput();
        }

        return redirect(route('categories.index'));
    }


    /**
     * @param CategoryFormRequest $request
     * @param SCRI                $repository
     * @param Category            $category
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(CategoryFormRequest $request, SCRI $repository, Category $category)
    {
        $categoryData = [
            'name' => $request->input('name'),
        ];

        $repository->update($category, $categoryData);

        Session::flash('success', 'Category "' . $category->name . '" updated.');
        Preferences::mark();

        if (intval(Input::get('return_to_edit')) === 1) {
            Session::put('categories.edit.fromUpdate', true);

            return redirect(route('categories.edit', [$category->id]));
        }

        // redirect to previous URL.
        return redirect(Session::get('categories.edit.url'));

    }

}
