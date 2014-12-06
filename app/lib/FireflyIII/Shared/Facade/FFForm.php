<?php

namespace FireflyIII\Shared\Facade;

use Illuminate\Support\Facades\Facade;

class FFForm extends Facade
{


    protected static function getFacadeAccessor()
    {
        return 'ffform';
    }

}