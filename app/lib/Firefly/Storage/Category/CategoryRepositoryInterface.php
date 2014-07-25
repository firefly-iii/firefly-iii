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
     * @param $name
     *
     * @return mixed
     */
    public function store($name);

} 