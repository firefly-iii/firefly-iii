<?php
/**
 * Category.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Mapper;

use Auth;
use FireflyIII\Models\Category as CategoryModel;

/**
 * Class Category
 *
 * @package FireflyIII\Helpers\Csv\Mapper
 */
class Category implements MapperInterface
{

    /**
     * @return array
     */
    public function getMap(): array
    {
        $result = Auth::user()->categories()->get(['categories.*']);
        $list   = [];

        /** @var CategoryModel $category */
        foreach ($result as $category) {
            $list[$category->id] = $category->name;
        }
        asort($list);

        $list = [0 => trans('firefly.csv_do_not_map')] + $list;

        return $list;
    }
}
