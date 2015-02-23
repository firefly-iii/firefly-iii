<?php

namespace FireflyIII\Repositories\Category;

use FireflyIII\Models\Category;

/**
 * Interface CategoryRepositoryInterface
 *
 * @package FireflyIII\Repositories\Category
 */
interface CategoryRepositoryInterface
{
    /**
     * @param Category $category
     *
     * @return boolean
     */
    public function destroy(Category $category);

    /**
     * @param array $data
     *
     * @return Category
     */
    public function store(array $data);

    /**
     * @param Category $category
     * @param array    $data
     *
     * @return Category
     */
    public function update(Category $category, array $data);

}