<?php

use Firefly\Helper\Controllers\CategoryInterface as CI;
use Firefly\Storage\Category\CategoryRepositoryInterface as CRI;

/**
 * Class CategoryController
 */
class CategoryController extends BaseController
{
    protected $_repository;

    public function __construct(CRI $repository, CI $category)
    {
        $this->_repository = $repository;
        $this->_category = $category;
        View::share('menu', 'categories');
    }

    public function create()
    {
        return View::make('categories.create');
    }

    public function delete(Category $category)
    {
        return View::make('categories.delete')->with('category', $category);
    }

    public function destroy()
    {
        $result = $this->_repository->destroy(Input::get('id'));
        if ($result === true) {
            Session::flash('success', 'The category was deleted.');
        } else {
            Session::flash('error', 'Could not delete the category. Check the logs to be sure.');
        }

        return Redirect::route('categories.index');
    }

    public function edit(Category $category)
    {
        return View::make('categories.edit')->with('category', $category);
    }

    public function index()
    {
        $categories = $this->_repository->get();

        return View::make('categories.index')->with('categories', $categories);
    }

    public function show(Category $category)
    {
        $start = \Session::get('start');
        $end = \Session::get('end');


        $journals = $this->_category->journalsInRange($category, $start, $end);

        return View::make('categories.show')->with('category', $category)->with('journals', $journals);
    }

    public function store()
    {
        $category = $this->_repository->store(Input::all());
        if ($category->id) {
            Session::flash('success', 'Category created!');

            if (Input::get('create') == '1') {
                return Redirect::route('categories.create');
            }

            return Redirect::route('categories.index');
        } else {
            Session::flash('error', 'Could not save the new category!');

            return Redirect::route('categories.create')->withInput();
        }
    }

    public function update()
    {
        $category = $this->_repository->update(Input::all());
        Session::flash('success', 'Category "' . $category->name . '" updated.');

        return Redirect::route('categories.index');
    }


}