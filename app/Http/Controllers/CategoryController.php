<?php namespace FireflyIII\Http\Controllers;

use Auth;
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\CategoryFormRequest;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Redirect;
use Session;
use View;

/**
 * Class CategoryController
 *
 * @package FireflyIII\Http\Controllers
 */
class CategoryController extends Controller
{

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
