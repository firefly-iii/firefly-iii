<?php

namespace FireflyIII\Validation;

use App;
use Auth;
use Carbon\Carbon;
use Config;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Validation\Validator;
use Navigation;
use Crypt;
use Log;

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
        $type = null;


        /**
         * Switch on different cases on which this method can respond:
         */
        $hasWhat = isset($this->data['what']);
        $hasAccountId = isset($this->data['account_type_id']) && isset($this->data['name']);

        if ($hasWhat) {

            $search = Config::get('firefly.accountTypeByIdentifier.' . $this->data['what']);
            $type   = AccountType::whereType($search)->first();
            // this field can be used to find the exact type, and continue.
        }
        if($hasAccountId) {
            $type   = AccountType::find($this->data['account_type_id']);
        }

        /**
         * Try to decrypt data just in case:
         */
        try {
            $value = Crypt::decrypt($value);
        } catch(DecryptException $e) {}


        if (is_null($type)) {
            Log::error('Could not determine type of account to validate.');
            return false;
        }

        // get all accounts with this type, and find the name.
        $userId = Auth::check() ? Auth::user()->id : 0;
        $set    = Account::where('account_type_id', $type->id)->where('user_id', $userId)->get();
        /** @var Account $entry */
        foreach ($set as $entry) {
            if ($entry->name == $value) {
                return false;
            }
        }

        return true;
        //        exit;
        //
        //
        //        // get account type from data, we must have this:
        //        $validTypes = array_keys(Config::get('firefly.subTitlesByIdentifier'));
        //        $dbType     = null;
        //        $type       = isset($this->data['what']) && in_array($this->data['what'], $validTypes) ? $this->data['what'] : null;
        //        // some fallback:
        //        if (is_null($type)) {
        //            $type = in_array(Input::get('what'), $validTypes) ? Input::get('what') : null;
        //
        //        }
        //
        //        // still null?
        //        if (is_null($type)) {
        //            // find by other field:
        //            $findType = isset($this->data['account_type_id']) ? $this->data['account_type_id'] : 0;
        //            $dbType   = AccountType::find($findType);
        //            $type     = $findType == 0 ? null : $findType;
        //        }
        //        // STILL null?
        //
        //        if (is_null($type) && isset($this->data['id'])) {
        //            // check ID thingie
        //            $dbAccount = Account::find($this->data['id']);
        //            if (!$dbAccount) {
        //                Log::error('False because $dbAccount does not exist.');
        //
        //                return false;
        //            }
        //            $dbType = AccountType::find($dbAccount->account_type_id);
        //        } else {
        //            $dbType = AccountType::whereType()
        //        }
        //
        //        if (is_null($dbType)) {
        //            Log::error('False because $dbType is null.');
        //
        //            return false;
        //        }
        //
        //        // user id?
        //        $userId = Auth::check() ? Auth::user()->id : $this->data['user_id'];
        //
        //        $query = DB::table('accounts')->where('name', $value)->where('account_type_id', $dbType->id)->where('user_id', $userId);
        //
        //        if (isset($parameters[0])) {
        //            $query->where('id', '!=', $parameters[0]);
        //        }
        //        $count = $query->count();
        //        if ($count == 0) {
        //
        //            return true;
        //        }
        //
        //        return false;

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

    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validateUniquePiggyBankForUser($attribute, $value, $parameters)
    {
        $query = DB::table($parameters[0])->where('piggy_banks.' . $parameters[1], $value);
        $query->leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id');
        $query->where('accounts.user_id', Auth::user()->id);
        if (isset($paramers[2])) {
            $query->where('piggy_banks.id', '!=', $parameters[2]);
        }
        $count = $query->count();
        if ($count == 0) {
            return true;
        }

        return false;

    }
}

