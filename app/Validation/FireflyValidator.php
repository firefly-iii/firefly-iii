<?php

namespace FireflyIII\Validation;

use Auth;
use Carbon\Carbon;
use Config;
use DB;
use FireflyIII\Models\AccountType;
use Illuminate\Validation\Validator;
use Input;
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

    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
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

        $nextReminder = Navigation::addPeriod($startDate, $array['reminder'], 0);
        // reminder is beyond target?
        if ($nextReminder > $targetDate) {
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
    public function validateUniqueAccountForUser($attribute, $value, $parameters)
    {
        // get account type from data, we must have this:
        $type     = isset($this->data['what']) ? $this->data['what'] : null;
        // some fallback:
        if(is_null($type)) {
            $type = Input::get('what');
        }
        // still null?
        if(is_null($type)) {
            // find by other field:
            $type = isset($this->data['account_type_id']) ? $this->data['account_type_id'] : 0;
            $dbType   = AccountType::find($type);
        } else {
            $longType = Config::get('firefly.accountTypeByIdentifier.' . $type);
            $dbType   = AccountType::whereType($longType)->first();
        }

        if (is_null($dbType)) {
            return false;
        }

        // user id?
        $userId = Auth::check() ? Auth::user()->id : $this->data['user_id'];

        $query = DB::table('accounts')->where('name', $value)->where('account_type_id', $dbType->id)->where('user_id', $userId);

        if (isset($parameters[0])) {
            $query->where('id', '!=', $parameters[0]);
        }
        $count = $query->count();
        if ($count == 0) {

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
        $query = DB::table($parameters[0])->where($parameters[1], $value);
        $query->where('user_id', Auth::user()->id);
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

