<?php

namespace FireflyIII\Validation;

use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Validation\Validator;
use Navigation;

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

    public function validatePiggyBankReminder($attribute, $value, $parameters)
    {
        $array = $this->data;
        // no reminder? dont care.
        if (!isset($array['remind_me'])) {
            return true;
        }

        // get or set start date & target date:
        $startDate  = isset($array['startdate']) ? new Carbon($array['startdate']) : new Carbon;
        $targetDate = isset($array['targetdate']) && strlen($array['targetdate']) > 0 ? new Carbon($array['targetdate']) : null;

        // target date is null? reminder period is always good.
        if ($array['remind_me'] == '1' && is_null($targetDate)) {
            return true;
        }

        $nextReminder = Navigation::addPeriod($startDate, $array['reminder'],0);
        // reminder is beyond target?
        if($nextReminder > $targetDate) {
            return false;
        }
        return true;
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
        $query = DB::table($parameters[0])->where($parameters[1], $value);
        $query->where('user_id',Auth::user()->id);
        if (isset($paramers[2])) {
            $query->where('id', '!=', $parameters[2]);
        }
        $count = $query->count();
        if ($count == 0) {
            return true;
        }

        return false;

    }
}

