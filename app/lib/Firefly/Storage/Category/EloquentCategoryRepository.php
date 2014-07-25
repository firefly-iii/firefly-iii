<?php

namespace Firefly\Storage\Category;

/**
 * Class EloquentCategoryRepository
 *
 * @package Firefly\Storage\Category
 */
class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    /**
     * @return mixed
     */
    public function get()
    {
        return \Auth::user()->categories()->get();
    }

    /**
     * @param $name
     *
     * @return \Category|mixed
     */
    public function createOrFind($name)
    {
        $category = $this->findByName($name);
        if (!$category) {
            return $this->store($name);
        }
        return $category;


    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function findByName($name)
    {
        return \Auth::user()->categories()->where('name', 'LIKE', '%' . $name . '%')->first();

    }

    /**
     * @param $name
     *
     * @return \Category|mixed
     */
    public function store($name)
    {
        $category = new \Category();
        $category->name = $name;
        $category->user()->associate(\Auth::user());
        $category->save();
        return $category;
    }

} 