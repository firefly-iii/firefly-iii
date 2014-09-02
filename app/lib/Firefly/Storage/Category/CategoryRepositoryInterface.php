<?php

namespace Firefly\Storage\Category;

/**
 * Interface CategoryRepositoryInterface
 *
 * @package Firefly\Storage\Category
 */
interface CategoryRepositoryInterface
{

    /**
     * @return mixed
     */
    public function get();

    /**
     * @param $categoryId
     *
     * @return mixed
     */
    public function find($categoryId);

    /**
     * @param $name
     *
     * @return mixed
     */
    public function createOrFind($name);

    /**
     * @param \User $user
     * @return mixed
     */
    public function overruleUser(\User $user);

    /**
     * @param $name
     *
     * @return mixed
     */
    public function findByName($name);

    /**
     * @param $data
     *
     * @return mixed
     */
    public function store($data);

    /**
     * @param $category
     * @param $data
     *
     * @return mixed
     */
    public function update($category, $data);

    /**
     * @param $category
     *
     * @return mixed
     */
    public function destroy($category);

} 