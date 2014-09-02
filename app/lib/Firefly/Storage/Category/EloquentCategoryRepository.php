<?php

namespace Firefly\Storage\Category;

/**
 * Class EloquentCategoryRepository
 *
 * @package Firefly\Storage\Category
 */
class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    protected $_user = null;

    /**
     *
     */
    public function __construct()
    {
        $this->_user = \Auth::user();
    }

    /**
     * @param $name
     *
     * @return \Category|mixed
     */
    public function createOrFind($name)
    {
        if (strlen($name) == 0) {
            return null;
        }
        $category = $this->findByName($name);
        if (!$category) {
            return $this->store(['name' => $name]);
        }

        return $category;


    }

    /**
     * @param $category
     *
     * @return bool|mixed
     */
    public function destroy($category)
    {
        $category->delete();

        return true;
    }

    /**
     * @param $categoryId
     *
     * @return mixed
     */
    public function find($categoryId)
    {
        return $this->_user->categories()->find($categoryId);
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function findByName($name)
    {
        if ($name == '' || strlen($name) == 0) {
            return null;
        }

        return $this->_user->categories()->where('name', 'LIKE', '%' . $name . '%')->first();

    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->_user->categories()->orderBy('name', 'ASC')->get();
    }

    /**
     * @param $data
     *
     * @return \Category|mixed
     */
    public function store($data)
    {
        $category       = new \Category;
        $category->name = $data['name'];

        $category->user()->associate($this->_user);
        $category->save();

        return $category;
    }

    /**
     * @param $category
     * @param $data
     *
     * @return mixed
     */
    public function update($category, $data)
    {
        // update account accordingly:
        $category->name = $data['name'];
        if ($category->validate()) {
            $category->save();
        }

        return $category;
    }

    /**
     * @param \User $user
     * @return mixed|void
     */
    public function overruleUser(\User $user)
    {
        $this->_user = $user;
        return true;
    }
} 