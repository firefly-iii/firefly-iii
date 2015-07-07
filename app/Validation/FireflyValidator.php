<?php

namespace FireflyIII\Validation;

use Auth;
use Config;
use Crypt;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\PiggyBank;
use FireflyIII\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Validation\Validator;
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
     * @param $attribute
     * @param $value
     *
     * @return bool
     */
    public function validateIban($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }

        if (strlen($value) === 0) {
            return false;
        }

        $value = strtoupper($value);
        if (strlen($value) < 6) {
            return false;
        }

        $search  = [' ', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $replace = ['', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31',
                    '32', '33', '34', '35'];

        // take
        $first    = substr($value, 0, 4);
        $last     = substr($value, 4);
        $iban     = $last . $first;
        $iban     = str_replace($search, $replace, $iban);
        $checksum = bcmod($iban, '97');

        return (intval($checksum) === 1);
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
        // because a user does not have to be logged in (tests and what-not).
        if (!Auth::check()) {
            return $this->validateAccountAnonymously();
        }

        if (isset($this->data['what'])) {
            return $this->validateByAccountTypeString($value, $parameters);
        }

        if (isset($this->data['account_type_id'])) {
            return $this->validateByAccountTypeId($value, $parameters);
        }
        if (isset($this->data['id'])) {
            return $this->validateByAccountId($value);
        }


        return false;
    }

    /**
     * @return bool
     */
    protected function validateAccountAnonymously()
    {
        if (!isset($this->data['user_id'])) {
            return false;
        }

        $user  = User::find($this->data['user_id']);
        $type  = AccountType::find($this->data['account_type_id'])->first();
        $value = $this->tryDecrypt($this->data['name']);


        $set = $user->accounts()->where('account_type_id', $type->id)->get();
        /** @var Account $entry */
        foreach ($set as $entry) {
            if ($entry->name == $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    protected function tryDecrypt($value)
    {
        try {
            $value = Crypt::decrypt($value);
        } catch (DecryptException $e) {
            // do not care.
        }

        return $value;
    }

    /**
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    protected function validateByAccountTypeString($value, $parameters)
    {
        $search = Config::get('firefly.accountTypeByIdentifier.' . $this->data['what']);
        $type   = AccountType::whereType($search)->first();
        $ignore = isset($parameters[0]) ? intval($parameters[0]) : 0;

        $set = Auth::user()->accounts()->where('account_type_id', $type->id)->where('id', '!=', $ignore)->get();
        /** @var Account $entry */
        foreach ($set as $entry) {
            if ($entry->name == $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    protected function validateByAccountTypeId($value, $parameters)
    {
        $type   = AccountType::find($this->data['account_type_id'])->first();
        $ignore = isset($parameters[0]) ? intval($parameters[0]) : 0;
        $value  = $this->tryDecrypt($value);

        $set = Auth::user()->accounts()->where('account_type_id', $type->id)->where('id', '!=', $ignore)->get();
        /** @var Account $entry */
        foreach ($set as $entry) {
            if ($entry->name == $value) {
                return false;
            }
        }

        return true;

    }

    /**
     * @param $value
     *
     * @return bool
     * @internal param $parameters
     *
     */
    protected function validateByAccountId($value)
    {
        /** @var Account $existingAccount */
        $existingAccount = Account::find($this->data['id']);

        $type   = $existingAccount->accountType;
        $ignore = $existingAccount->id;
        $value  = $this->tryDecrypt($value);

        $set = Auth::user()->accounts()->where('account_type_id', $type->id)->where('id', '!=', $ignore)->get();
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
     * parameter 2: an id to ignore (when editing)
     *
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validateUniqueObjectForUser($attribute, $value, $parameters)
    {
        $value = $this->tryDecrypt($value);
        // exclude?
        $table   = $parameters[0];
        $field   = $parameters[1];
        $exclude = isset($parameters[2]) ? intval($parameters[2]) : 0;

        // get entries from table
        $set = DB::table($table)->where('user_id', Auth::user()->id)->where('id', '!=', $exclude)->get([$field]);

        foreach ($set as $entry) {
            $fieldValue = $this->tryDecrypt($entry->$field);

            if ($fieldValue === $value) {
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

        /** @var PiggyBank $entry */
        foreach ($set as $entry) {
            $fieldValue = $this->tryDecrypt($entry->name);
            if ($fieldValue == $value) {
                return false;
            }
        }

        return true;

    }
}

