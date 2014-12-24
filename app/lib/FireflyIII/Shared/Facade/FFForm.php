<?php

namespace FireflyIII\Shared\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Class FFForm
 *
 * @package FireflyIII\Shared\Facade
 */
class FFForm extends Facade
{


    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'ffform';
    }

}