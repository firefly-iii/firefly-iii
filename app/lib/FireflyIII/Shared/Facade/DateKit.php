<?php

namespace FireflyIII\Shared\Facade;

use Illuminate\Support\Facades\Facade;

class DateKit extends Facade
{


    protected static function getFacadeAccessor()
    {
        return 'datekit';
    }

}