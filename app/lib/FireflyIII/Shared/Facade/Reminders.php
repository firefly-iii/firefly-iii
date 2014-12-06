<?php

namespace FireflyIII\Shared\Facade;

use Illuminate\Support\Facades\Facade;

class Reminders extends Facade
{


    protected static function getFacadeAccessor()
    {
        return 'reminders';
    }

}