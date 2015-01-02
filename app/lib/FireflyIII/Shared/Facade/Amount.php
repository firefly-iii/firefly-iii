<?php

namespace FireflyIII\Shared\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Class Amount
 *
 * @package FireflyIII\Shared\Facade
 */
class Amount extends Facade
{


    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'amount';
    }

}
