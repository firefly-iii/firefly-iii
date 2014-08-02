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

    public function find($categoryId);

    /**
     * @param $name
     *
     * @return mixed
     */
    public function createOrFind($name);

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

    public function update($data);

    /**
     * @param $data
     *
     * @return mixed
     */
    public function destroy($categoryId);

} 