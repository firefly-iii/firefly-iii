<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use FireflyIII\Http\Requests\CategoryFormRequest;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Input;
use Redirect;
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
        View::share('title', trans('firefly.categories'));
        View::share('mainTitleIcon', 'fa-bar-chart');
    }

    /**
     * @return $this
     */
    public function create()
    {
        // put previous url in session if not redirect from store (not "create another").
        if (Session::get('categories.create.fromStore') !== true) {
            Session::put('categories.create.url', URL::previous());
        }
        Session::forget('categories.create.fromStore');
        $subTitle = 'Create a new category';

        return view('categories.create', compact('subTitle'));
    }

    /**
     * @param Category $category
     *
     * @return \Illuminate\View\View
     */
    public function delete(Category $category)
    {
        $subTitle = 'Delete category "' . e($category->name) . '"';

        // put previous url in session
        Session::put('categories.delete.url', URL::previous());

        return view('categories.delete', compact('category', 'subTitle'));
    }

    /**
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(CategoryRepositoryInterface $repository, Category $category)
    {

        $name = $category->name;
        $repository->destroy($category);

        Session::flash('success', 'The  category "' . e($name) . '" was deleted.');

        return Redirect::to(Session::get('categories.delete.url'));
    }

    /**
     * @param Category $category
     *
     * @return $this
     */
    public function edit(Category $category)
    {
        $subTitle = 'Edit category "' . e($category->name) . '"';

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (Session::get('categories.edit.fromUpdate') !== true) {
            Session::put('categories.edit.url', URL::previous());
        }
        Session::forget('categories.edit.fromUpdate');

        return view('categories.edit', compact('category', 'subTitle'));

    }

    /**
     * @param CategoryRepositoryInterface $repository
     *
     * @return $this
     */
    public function index(CategoryRepositoryInterface $repository)
    {
        $categories = $repository->getCategories();

        $categories->each(
            function (Category $category) use ($repository) {
                $category->lastActivity = $repository->getLatestActivity($category);
            }
        );

        return view('categories.index', compact('categories'));
    }

    /**
     * @param CategoryRepositoryInterface $repository
     *
     * @return \Illuminate\View\View
     */
    public function noCategory(CategoryRepositoryInterface $repository)
    {
        $start    = Session::get('start', Carbon::now()->startOfMonth());
        $end      = Session::get('end', Carbon::now()->startOfMonth());
        $list     = $repository->getWithoutCategory($start, $end);
        $subTitle = 'Transactions without a category between ' . $start->format('jS F Y') . ' and ' . $end->format('jS F Y');

        return view('categories.noCategory', compact('list', 'subTitle'));
    }

    /**
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     *
     * @return View
     */
    public function show(CategoryRepositoryInterface $repository, Category $category)
    {
        $hideCategory = true; // used in list.
        $page         = intval(Input::get('page'));
        $set          = $repository->getJournals($category, $page);
        $count        = $repository->countJournals($category);
        $journals     = new LengthAwarePaginator($set, $count, 50, $page);
        $journals->setPath('categories/show/' . $category->id);

        return view('categories.show', compact('category', 'journals', 'hideCategory'));
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

        if (intval(Input::get('create_another')) === 1) {
            Session::put('categories.create.fromStore', true);

            return Redirect::route('categories.create')->withInput();
        }

        return Redirect::route('categories.index');
    }


    /**
     * @param CategoryFormRequest         $request
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(CategoryFormRequest $request, CategoryRepositoryInterface $repository, Category $category)
    {
        $categoryData = [
            'name' => $request->input('name'),
        ];

        $repository->update($category, $categoryData);

        Session::flash('success', 'Category "' . $category->name . '" updated.');

        if (intval(Input::get('return_to_edit')) === 1) {
            Session::put('categories.edit.fromUpdate', true);

            return Redirect::route('categories.edit', $category->id);
        }

        // redirect to previous URL.
        return Redirect::to(Session::get('categories.edit.url'));

    }

}
