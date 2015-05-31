<?php

namespace FireflyIII\Validation;

use Auth;
use Carbon\Carbon;
use Config;
use Crypt;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Validation\Validator;
use Log;
use Navigation;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class FireflyValidator
 *
 * @package FireflyIII\Validation
 */
class FireflyValidator extends Validator
{

    /**
     * @param TranslatorInterface $translator
     * @param array               $data
     * @param array               $rules
     * @param array               $messages
     * @param array               $customAttributes
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(TranslatorInterface $translator, array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        parent::__construct($translator, $data, $rules, $messages, $customAttributes);
    }

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
     * @return bool
     */
    public function validatePiggyBankReminder()
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
        $hasWhat          = isset($this->data['what']);
        $hasAccountTypeId = isset($this->data['account_type_id']) && isset($this->data['name']);
        $hasAccountId     = isset($this->data['id']);
        $ignoreId         = 0;


        if ($hasWhat) {
            $search = Config::get('firefly.accountTypeByIdentifier.' . $this->data['what']);
            $type   = AccountType::whereType($search)->first();
            // this field can be used to find the exact type, and continue.
        }

        if ($hasAccountTypeId) {
            $type = AccountType::find($this->data['account_type_id']);
        }

        if ($hasAccountId) {
            /** @var Account $account */
            $account  = Account::find($this->data['id']);
            $ignoreId = intval($this->data['id']);
            $type     = AccountType::find($account->account_type_id);
            unset($account);
        }

        /**
         * Try to decrypt data just in case:
         */
        try {
            $value = Crypt::decrypt($value);
        } catch (DecryptException $e) {
            // if it fails, probably not encrypted.
        }


        if (is_null($type)) {
            Log::error('Could not determine type of account to validate.');

            return false;
        }

        // get all accounts with this type, and find the name.
        $userId = Auth::check() ? Auth::user()->id : 0;
        $set    = Account::where('account_type_id', $type->id)->where('id', '!=', $ignoreId)->where('user_id', $userId)->get();
        /** @var Account $entry */
        foreach ($set as $entry) {
            if ($entry->name == $value) {
                return false;
            }
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
        $query->where('user_id', Auth::user()->id);
        if (isset($parameters[2])) {
            $query->where('id', '!=', $parameters[2]);
        }
        $count = $query->count();
        if ($count == 0) {
            return true;
        }

        return false;

    }

    /**
     * Validate an object and its unicity. Checks for encryption / encrypted values as well.
     *
     * parameter 0: the table
     * parameter 1: the field
     * parameter 2: the encrypted / not encrypted boolean. Defaults to "encrypted".
     * parameter 3: an id to ignore (when editing)
     *
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validateUniqueObjectForUser($attribute, $value, $parameters)
    {
        $table           = $parameters[0];
        $field           = $parameters[1];
        $encrypted       = isset($parameters[2]) ? $parameters[2] : 'encrypted';
        $exclude         = isset($parameters[3]) ? $parameters[3] : null;
        $alwaysEncrypted = false;
        if ($encrypted == 'TRUE') {
            $alwaysEncrypted = true;
        }

        if (is_null(Auth::user())) {
            // user is not logged in.. weird.
            return true;
        } else {
            $query = DB::table($table)->where('user_id', Auth::user()->id);
        }


        if (!is_null($exclude)) {
            $query->where('id', '!=', $exclude);
        }


        $set = $query->get();
        foreach ($set as $entry) {
            if (!$alwaysEncrypted) {
                $isEncrypted = intval($entry->$encrypted) == 1 ? true : false;
            } else {
                $isEncrypted = true;
            }
            $checkValue = $isEncrypted ? Crypt::decrypt($entry->$field) : $entry->$field;
            if ($checkValue == $value) {
                return false;
            }
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
    public function validateUniquePiggyBankForUser($attribute, $value, $parameters)
    {
        $exclude = isset($parameters[0]) ? $parameters[0] : null;
        $query   = DB::table('piggy_banks');
        $query->leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id');
        $query->where('accounts.user_id', Auth::user()->id);
        if (!is_null($exclude)) {
            $query->where('piggy_banks.id', '!=', $exclude);
        }
        $set = $query->get(['piggy_banks.*']);

        foreach ($set as $entry) {
            $isEncrypted = intval($entry->encrypted) == 1 ? true : false;
            $checkValue  = $isEncrypted ? Crypt::decrypt($entry->name) : $entry->name;
            if ($checkValue == $value) {
                return false;
            }
        }

        return true;

    }
}

