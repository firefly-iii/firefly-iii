<?php

namespace FireflyIII\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Steam
 *
 * @package FireflyIII\Support\Facades
 */
class Steam extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'steam';
    }

}
