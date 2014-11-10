<?php
use Firefly\Exception\FireflyException;
use Illuminate\Support\MessageBag;

/**
 * Class CategoryController
 */
class CategoryController extends BaseController
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
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return View::make('categories.create')->with('subTitle', 'Create a new category');
    }

    /**
     * @param Category $category
     *
     * @return $this
     */
    public function delete(Category $category)
    {
        return View::make('categories.delete')->with('category', $category)->with('subTitle', 'Delete category "' . $category->name . '"');
    }

    /**
     * @param Category $category
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Category $category)
    {
        /** @var \FireflyIII\Database\Category $repos */
        $repos = App::make('FireflyIII\Database\Category');

        $repos->destroy($category);
        Session::flash('success', 'The category was deleted.');
        return Redirect::route('categories.index');
    }

    /**
     * @param Category $category
     *
     * @return $this
     */
    public function edit(Category $category)
    {
        return View::make('categories.edit')->with('category', $category)
                   ->with('subTitle', 'Edit category "' . $category->name . '"');
    }

    /**
     * @return $this
     */
    public function index()
    {
        return View::make('categories.index');
    }

    /**
     * @param Category $category
     *
     * @return $this
     */
    public function show(Category $category)
    {
        return View::make('categories.show', compact('category'));
    }

    /**
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        $category = $this->_repository->store(Input::all());
        if ($category->validate()) {
            Session::flash('success', 'Category "' . $category->name . '" created!');

            if (Input::get('create') == '1') {
                return Redirect::route('categories.create');
            }

            return Redirect::route('categories.index');
        } else {
            Session::flash('error', 'Could not save the new category!');

            return Redirect::route('categories.create')->withInput();
        }
    }

    /**
     * @param Category $category
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function update(Category $category)
    {
        /** @var \FireflyIII\Database\Category $repos */
        $repos = App::make('FireflyIII\Database\Category');
        $data  = Input::except('_token');

        switch (Input::get('post_submit_action')) {
            default:
                throw new FireflyException('Cannot handle post_submit_action "' . e(Input::get('post_submit_action')) . '"');
                break;
            case 'return_to_edit':
            case 'update':
                $messages = $repos->validate($data);
                /** @var MessageBag $messages ['errors'] */
                if ($messages['errors']->count() > 0) {
                    Session::flash('warnings', $messages['warnings']);
                    Session::flash('successes', $messages['successes']);
                    Session::flash('error', 'Could not save category: ' . $messages['errors']->first());
                    return Redirect::route('categories.edit', $category->id)->withInput()->withErrors($messages['errors']);
                }
                // store!
                $repos->update($category, $data);
                Session::flash('success', 'Category updated!');

                if ($data['post_submit_action'] == 'return_to_edit') {
                    return Redirect::route('categories.edit', $category->id);
                } else {
                    return Redirect::route('categories.index');
                }
            case 'validate_only':
                $messageBags = $repos->validate($data);
                Session::flash('warnings', $messageBags['warnings']);
                Session::flash('successes', $messageBags['successes']);
                Session::flash('errors', $messageBags['errors']);
                return Redirect::route('categories.edit', $category->id)->withInput();
                break;
        }


    }


}