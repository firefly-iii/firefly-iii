<?php
declare(strict_types = 1);
/**
 * CategoryList.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Support\Binder;

use Auth;
use FireflyIII\Models\Category;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class CategoryList
 *
 * @package FireflyIII\Support\Binder
 */
class CategoryList implements BinderInterface
{

    /**
     * @param $value
     * @param $route
     *
     * @return mixed
     */
    public static function routeBinder($value, $route)
    {
        if (Auth::check()) {
            $ids = explode(',', $value);
            /** @var \Illuminate\Support\Collection $object */
            $object = Category::whereIn('id', $ids)
                              ->where('user_id', Auth::user()->id)
                              ->get();

            // add empty budget if applicable.
            if (in_array('0', $ids)) {
                $object->push(new Category);
            }

            if ($object->count() > 0) {
                return $object;
            }
        }
        throw new NotFoundHttpException;
    }
}
