<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\CategoryFormRequest;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Redirect;
use Session;
use View;
use Input;


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
        View::share('title', 'Categories');
        View::share('mainTitleIcon', 'fa-bar-chart');
    }

    /**
     * @return $this
     */
    public function create()
    {
        return view('categories.create')->with('subTitle', 'Create a new category');
    }

    /**
     * @param Category $category
     *
     * @return $this
     */
    public function show(Category $category, CategoryRepositoryInterface $repository)
    {
        $hideCategory = true; // used in list.
        $page = intval(Input::get('page'));
        $offset = $page > 0 ? $page * 50 : 0;
        $set    = $category->transactionJournals()->withRelevantData()->take(50)->offset($offset)->orderBy('date', 'DESC')->get(['transaction_journals.*']);
        $count  = $category->transactionJournals()->count();

        $journals = new LengthAwarePaginator($set, $count, 50, $page);

        return view('categories.show', compact('category', 'journals', 'hideCategory'));
    }

    /**
     * @return \Illuminate\View\View
     */
    public function noCategory()
    {
        $start    = Session::get('start', Carbon::now()->startOfMonth());
        $end      = Session::get('end', Carbon::now()->startOfMonth());
        $list     = Auth::user()
                         ->transactionjournals()
                         ->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                         ->whereNull('category_transaction_journal.id')
                         ->before($end)
                         ->after($start)
                         ->orderBy('transaction_journals.date')
                         ->get(['transaction_journals.*']);
        $subTitle = 'Transactions without a category in ' . $start->format('F Y');

        return View::make('categories.noCategory', compact('list', 'subTitle'));
    }

    /**
     * @param Category $category
     *
     * @return \Illuminate\View\View
     */
    public function delete(Category $category)
    {
        $subTitle = 'Delete category' . e($category->name) . '"';

        return view('categories.delete', compact('category', 'subTitle'));
    }

    /**
     * @param Category $category
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Category $category, CategoryRepositoryInterface $repository)
    {

        $name = $category->name;
        $repository->destroy($category);

        Session::flash('success', 'The  category "' . e($name) . '" was deleted.');

        return Redirect::route('categories.index');
    }

    /**
     * @param Category $category
     *
     * @return $this
     */
    public function edit(Category $category)
    {
        $subTitle = 'Edit category "' . e($category->name) . '"';

        return view('categories.edit', compact('category', 'subTitle'));

    }

    /**
     * @return $this
     */
    public function index()
    {
        $categories = Auth::user()->categories()->get();

        return view('categories.index', compact('categories'));
    }

    /**
     * @param CategoryFormRequest         $request
     * @param CategoryRepositoryInterface $repository
     *
     * @return mixed
     */
    public function store(CategoryFormRequest $request, CategoryRepositoryInterface $repository)
    {
        $categoryData = [
            'name' => $request->input('name'),
            'user' => Auth::user()->id,
        ];
        $category     = $repository->store($categoryData);

        Session::flash('success', 'New category "' . $category->name . '" stored!');

        return Redirect::route('categories.index');

    }


    /**
     * @param Category                    $category
     * @param CategoryFormRequest         $request
     * @param CategoryRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Category $category, CategoryFormRequest $request, CategoryRepositoryInterface $repository)
    {
        $categoryData = [
            'name' => $request->input('name'),
        ];

        $repository->update($category, $categoryData);

        Session::flash('success', 'Category "' . $category->name . '" updated.');

        return Redirect::route('categories.index');

    }

}
