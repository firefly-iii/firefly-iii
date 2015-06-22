<?php

namespace FireflyIII\Helpers\Collection;

use FireflyIII\Models\Category as CategoryModel;
use Illuminate\Support\Collection;


/**
 * @codeCoverageIgnore
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
        if ($category->spent > 0) {
            $this->categories->push($category);
        }
    }

    /**
     * @param float $add
     */
    public function addTotal($add)
    {
        $add = strval(round($add, 2));
        bcscale(2);
        $this->total = bcadd($this->total, $add);
    }

    /**
     * @return Collection
     */
    public function getCategories()
    {
        $set = $this->categories->sortByDesc(
            function (CategoryModel $category) {
                return $category->spent;
            }
        );


        return $set;
    }

    /**
     * @return string
     */
    public function getTotal()
    {
        return strval(round($this->total, 2));
    }


}
