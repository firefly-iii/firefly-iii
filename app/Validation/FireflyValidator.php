<?php

namespace FireflyIII\Validation;

use DB;
use Illuminate\Validation\Validator;

/**
 * Class FireflyValidator
 *
 * @package FireflyIII\Validation
 */
class FireflyValidator extends Validator
{

    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validateUniqueForUser($attribute, $value, $parameters)
    {
        $count = DB::table($parameters[0])->where($parameters[1], $value)->count();
        if ($count == 0) {
            return true;
        }

        return false;

    }
}

