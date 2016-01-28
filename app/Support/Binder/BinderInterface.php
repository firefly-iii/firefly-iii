<?php
/**
 * BinderInterface.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Support\Binder;

/**
 * Interface BinderInterface
 *
 * @package FireflyIII\Support\Binder
 */
interface BinderInterface
{
    /**
     * @param $value
     * @param $route
     *
     * @return mixed
     */
    public static function routeBinder($value, $route);

}
