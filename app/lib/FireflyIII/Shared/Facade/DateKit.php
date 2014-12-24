<?php

namespace FireflyIII\Shared\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Class DateKit
 *
 * @package FireflyIII\Shared\Facade
 */
class DateKit extends Facade
{


    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'datekit';
    }

}