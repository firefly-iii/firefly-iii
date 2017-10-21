<?php
/**
 * Category.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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
