<?php

namespace FireflyIII\Shared\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Class Reminders
 *
 * @package FireflyIII\Shared\Facade
 */
class Reminders extends Facade
{


    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'reminders';
    }

}
