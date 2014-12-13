<?php

namespace FireflyIII\Shared\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Class Filter
 *
 * @package FireflyIII\Shared\Facade
 */
class Filter extends Facade
{


    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'filter';
    }

}