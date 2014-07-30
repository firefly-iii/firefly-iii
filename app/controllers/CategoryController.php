<?php

use Firefly\Storage\Category\CategoryRepositoryInterface as CRI;

/**
 * Class CategoryController
 */
class CategoryController extends BaseController
{
    protected $_repository;

    public function __construct(CRI $repository)
    {
        $this->_repository = $repository;
        View::share('menu', 'categories');
    }

    public function create()
    {
    }

    public function delete(Category $category)
    {
        return View::make('categories.delete')->with('category',$category);
    }

    public function destroy()
    {
    }

    public function edit(Category $category)
    {
        return View::make('categories.edit')->with('category',$category);
    }

    public function index()
    {
        $categories = $this->_repository->get();
        return View::make('categories.index')->with('categories',$categories);
    }

    public function show(Category $category)
    {
        return View::make('categories.show')->with('category',$category);
    }

    public function store()
    {
    }

    public function update()
    {
    }


}