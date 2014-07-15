<?php

namespace Firefly\Storage\Category;


interface CategoryRepositoryInterface
{

    public function get();

    public function createOrFind($name);

    public function findByName($name);

    public function store($name);

} 