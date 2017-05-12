<?php
/**
 * Category.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Collection;

use FireflyIII\Models\Category as CategoryModel;
use Illuminate\Support\Collection;


/**
 *
 * Class Category
 *
 * @package FireflyIII\Helpers\Collection
 */
class Category
{

    /** @var  Collection */
    protected $categories;
    /** @var string */
    protected $total = '0';

    /**
     *
     */
    public function __construct()
    {
        $this->categories = new Collection;
    }

    /**
     * @param CategoryModel $category
     */
    public function addCategory(CategoryModel $category)
    {
        // spent is minus zero for an expense report:
        if ($category->spent < 0) {
            $this->categories->push($category);
            $this->addTotal($category->spent);
        }
    }

    /**
     * @param string $add
     */
    public function addTotal(string $add)
    {
        $this->total = bcadd($this->total, $add);
    }

    /**
     * @return Collection
     */
    public function getCategories(): Collection
    {
        $set = $this->categories->sortBy(
            function (CategoryModel $category) {
                return $category->spent;
            }
        );


        return $set;
    }

    /**
     * @return string
     */
    public function getTotal(): string
    {
        return $this->total;
    }


}
