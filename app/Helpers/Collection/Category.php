<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 16/05/15
 * Time: 13:09
 */

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
    /** @var float */
    protected $total = 0;

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
        $this->total += floatval($add);
    }

    /**
     * @return Collection
     */
    public function getCategories()
    {
        $this->categories->sortByDesc(
            function (CategoryModel $category) {
                return $category->spent;
            }
        );


        return $this->categories;
    }

    /**
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }


}
