<?php

namespace FireflyIII\Shared\Facade;

use Illuminate\Support\Facades\Facade;

class Steam extends Facade
{


    protected static function getFacadeAccessor()
    {
        return 'steam';
    }

}