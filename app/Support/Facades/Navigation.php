<?php
/**
 * Navigation.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Navigation
 *
 * @package FireflyIII\Support\Facades
 */
class Navigation extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'navigation';
    }

}
