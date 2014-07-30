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
        return \Auth::user()->categories()->orderBy('name', 'ASC')->get();
    }

    public function find($categoryId)
    {
        return \Auth::user()->categories()->find($categoryId);
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
            return $this->store(['name' => $name]);
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
        if ($name == '') {
            return null;
        }

        return \Auth::user()->categories()->where('name', 'LIKE', '%' . $name . '%')->first();

    }

    /**
     * @param $name
     *
     * @return \Category|mixed
     */
    public function store($data)
    {
        $category = new \Category;
        $category->name = $data['name'];

        $category->user()->associate(\Auth::user());
        $category->save();
        return $category;
    }

    public function update($data)
    {
        $category = $this->find($data['id']);
        if ($category) {
            // update account accordingly:
            $category->name = $data['name'];
            if ($category->validate()) {
                $category->save();
            }
        }

        return $category;
    }

    public function destroy($categoryId)
    {
        $category = $this->find($categoryId);
        if ($category) {
            $category->delete();

            return true;
        }

        return false;
    }
} 