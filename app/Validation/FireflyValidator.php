<?php

namespace FireflyIII\Validation;

use Auth;
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
    public function validateBelongsToUser($attribute, $value, $parameters)
    {
        $count = DB::table($parameters[0])->where('user_id', Auth::user()->id)->where('id', $value)->count();
        if ($count == 1) {
            return true;
        }

        return false;

    }

    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validateUniqueForUser($attribute, $value, $parameters)
    {
        $count = DB::table($parameters[0])->where($parameters[1], $value)->where('id', '!=', $parameters[2])->count();
        if ($count == 0) {
            return true;
        }

        return false;

    }
}

