<?php

namespace FireflyIII\Shared\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Class Steam
 *
 * @package FireflyIII\Shared\Facade
 */
class Steam extends Facade
{


    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'steam';
    }

}
