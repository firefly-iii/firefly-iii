<?php

namespace FireflyIII\Validation;

use Illuminate\Validation\Validator;
use DB;

class FireflyValidator extends Validator
{

    public function validateUniqueForUser($attribute, $value, $parameters)
    {
        $count = DB::table($parameters[0])->where($parameters[1],$value)->count();
        if($count == 0) {
            return true;
        }
        return false;

    }
}

