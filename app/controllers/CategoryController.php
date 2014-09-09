<?php

use Firefly\Helper\Controllers\CategoryInterface as CI;
use Firefly\Storage\Category\CategoryRepositoryInterface as CRI;

/**
 * Class CategoryController
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class CategoryController extends BaseController
{
    protected $_repository;
    protected $_category;

    /**
     * @param CRI $repository
     * @param CI  $category
     */
    public function __construct(CRI $repository, CI $category)
    {
        $this->_repository = $repository;
        $this->_category   = $category;
    }

    /**
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return View::make('categories.create')->with('title', 'Create a new category');
    }

    /**
     * @param Category $category
     *
     * @return $this
     */
    public function delete(Category $category)
    {
        return View::make('categories.delete')->with('category', $category)
            ->with('title', 'Delete category "' . $category->name . '"');
    }

    /**
     * @param Category $category
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Category $category)
    {
        $this->_repository->destroy($category);
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
            ->with('title', 'Edit category "' . $category->name . '"');
    }

    /**
     * @return $this
     */
    public function index()
    {
        $categories = $this->_repository->get();

        return View::make('categories.index')->with('categories', $categories)
            ->with('title', 'All your categories');
    }

    /**
     * @param Category $category
     *
     * @return $this
     */
    public function show(Category $category)
    {
        $start = \Session::get('start');
        $end   = \Session::get('end');


        $journals = $this->_category->journalsInRange($category, $start, $end);

        return View::make('categories.show')->with('category', $category)->with('journals', $journals)->with(
            'highlight', Input::get('highlight')
        )->with('title', 'Overview for category "' . $category->name . '"');
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
        $category = $this->_repository->update($category, Input::all());
        if ($category->validate()) {
            Session::flash('success', 'Category "' . $category->name . '" updated.');

            return Redirect::route('categories.index');
        } else {
            Session::flash('success', 'Could not update category "' . $category->name . '".');

            return Redirect::route('categories.edit')->withErrors($category->errors())->withInput();
        }


    }


}