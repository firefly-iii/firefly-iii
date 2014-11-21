<?php

namespace FireflyIII\Shared\Facade;

use Illuminate\Support\Facades\Facade;

class Navigation extends Facade
{


    protected static function getFacadeAccessor()
    {
        return 'navigation';
    }

}