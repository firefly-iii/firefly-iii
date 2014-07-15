<?php

namespace Firefly\Storage\Category;


class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function get()
    {
        return \Auth::user()->categories()->get();
    }

    public function createOrFind($name)
    {
        $category = $this->findByName($name);
        if (!$category) {
            return $this->store($name);
        }
        return $category;


    }

    public function findByName($name)
    {
        return \Auth::user()->categories()->where('name', 'LIKE', '%' . $name . '%')->first();

    }

    public function store($name)
    {
        $category = new \Category();
        $category->name = $name;
        $category->user()->associate(\Auth::user());
        $category->save();
        return $category;
    }

} 