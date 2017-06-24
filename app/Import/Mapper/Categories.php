<?php
/**
 * Categories.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Mapper;


use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;

/**
 * Class Categories
 *
 * @package FireflyIII\Import\Mapper
 */
class Categories implements MapperInterface
{

    /**
     * @return array
     */
    public function getMap(): array
    {
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $result     = $repository->getCategories();
        $list       = [];

        /** @var Category $category */
        foreach ($result as $category) {
            $list[$category->id] = $category->name;
        }
        asort($list);

        $list = [0 => trans('csv.map_do_not_map')] + $list;

        return $list;

    }
}
