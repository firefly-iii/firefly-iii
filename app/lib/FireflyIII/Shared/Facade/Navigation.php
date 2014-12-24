<?php

namespace FireflyIII\Shared\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Class Navigation
 *
 * @package FireflyIII\Shared\Facade
 */
class Navigation extends Facade
{


    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'navigation';
    }

}