<?php
/**
 * FireflyValidator.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Validation;

use Config;
use Crypt;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Rules\Triggers\TriggerInterface;
use FireflyIII\User;
use Google2FA;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Validation\Validator;
use Session;
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
     *
     */
    public function __construct(TranslatorInterface $translator, array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        parent::__construct($translator, $data, $rules, $messages, $customAttributes);
    }

    /**
     * @param $attribute
     * @param $value
     *
     * @return bool
     *
     */
    public function validate2faCode($attribute, $value): bool
    {
        if (!is_string($value) || is_null($value) || strlen($value) <> 6) {
            return false;
        }

        $secret = Session::get('two-factor-secret');

        return Google2FA::verifyKey($secret, $value);
    }

    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validateBelongsToUser($attribute, $value, $parameters): bool
    {
        $field = $parameters[1] ?? 'id';

        if (intval($value) === 0) {
            return true;
        }
        $count = DB::table($parameters[0])->where('user_id', auth()->user()->id)->where($field, $value)->count();
        if ($count === 1) {
            return true;
        }

        return false;

    }

    /**
     * @param $attribute
     * @param $value
     *
     * @return bool
     *
     */
    public function validateBic($attribute, $value): bool
    {
        $regex  = '/^[a-z]{6}[0-9a-z]{2}([0-9a-z]{3})?\z/i';
        $result = preg_match($regex, $value);
        if ($result === false) {
            return false;
        }
        if ($result === 0) {
            return false;
        }

        return true;
    }

    /**
     * @param $attribute
     * @param $value
     *
     *
     * @return bool
     */
    public function validateIban($attribute, $value): bool
    {
        if (!is_string($value) || is_null($value) || strlen($value) < 6) {
            return false;
        }

        $value = strtoupper($value);

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
    public function validateMustExist($attribute, $value, $parameters): bool
    {
        $field = $parameters[1] ?? 'id';

        if (intval($value) === 0) {
            return true;
        }
        $count = DB::table($parameters[0])->where($field, $value)->count();
        if ($count === 1) {
            return true;
        }

        return false;
    }

    /**
     * @param $attribute
     *
     * @return bool
     */
    public function validateRuleActionValue($attribute): bool
    {
        // get the index from a string like "rule-action-value.2".
        $parts = explode('.', $attribute);
        $index = $parts[count($parts) - 1];
        // loop all rule-actions.
        // check if rule-action-value matches the thing.

        if (is_array($this->data['rule-action'])) {
            $name  = $this->data['rule-action'][$index] ?? 'invalid';
            $value = $this->data['rule-action-value'][$index] ?? false;
            switch ($name) {
                default:

                    return true;
                case 'set_budget':
                    /** @var BudgetRepositoryInterface $repository */
                    $repository = app(BudgetRepositoryInterface::class);
                    $budgets    = $repository->getBudgets();
                    // count budgets, should have at least one
                    $count = $budgets->filter(
                        function (Budget $budget) use ($value) {
                            return $budget->name == $value;
                        }
                    )->count();

                    return ($count === 1);
                case 'invalid':
                    return false;

            }
        }

        return false;
    }

    /**
     * @param $attribute
     *
     * @return bool
     */
    public function validateRuleTriggerValue($attribute): bool
    {
        // get the index from a string like "rule-trigger-value.2".
        $parts = explode('.', $attribute);
        $index = $parts[count($parts) - 1];

        // loop all rule-triggers.
        // check if rule-value matches the thing.
        if (is_array($this->data['rule-trigger'])) {
            $name  = $this->getRuleTriggerName($index);
            $value = $this->getRuleTriggerValue($index);

            // break on some easy checks:
            switch ($name) {
                case 'amount_less':
                    $result = is_numeric($value);
                    if ($result === false) {
                        return false;
                    }
                    break;
                case 'transaction_type':
                    $count = TransactionType::where('type', $value)->count();
                    if (!($count === 1)) {
                        return false;
                    }
                    break;
                case 'invalid':
                    return false;
            }
            // still a special case where the trigger is
            // triggered in such a way that it would trigger ANYTHING. We can check for such things
            // with function willmatcheverything
            // we know which class it is so dont bother checking that.
            $classes = Config::get('firefly.rule-triggers');
            /** @var TriggerInterface $class */
            $class = $classes[$name];

            return !($class::willMatchEverything($value));

        }

        return false;
    }

    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     *
     * @return bool
     */
    public function validateUniqueAccountForUser($attribute, $value, $parameters): bool
    {
        // because a user does not have to be logged in (tests and what-not).
        if (!auth()->check()) {
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
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validateUniqueAccountNumberForUser($attribute, $value, $parameters): bool
    {
        $accountId = $this->data['id'] ?? 0;

        $query = AccountMeta::
        leftJoin('accounts', 'accounts.id', '=', 'account_meta.account_id')
                            ->where('accounts.user_id', auth()->user()->id)
                            ->where('account_meta.name', 'accountNumber');

        if (intval($accountId) > 0) {
            // exclude current account from check.
            $query->where('account_meta.account_id', '!=', intval($accountId));
        }
        $set = $query->get(['account_meta.*']);

        /** @var AccountMeta $entry */
        foreach ($set as $entry) {
            if ($entry->data == $value) {

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
     *
     * @return bool
     */
    public function validateUniqueForUser($attribute, $value, $parameters): bool
    {
        $query = DB::table($parameters[0])->where($parameters[1], $value);
        $query->where('user_id', auth()->user()->id);
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
     *
     * @return bool
     */
    public function validateUniqueObjectForUser($attribute, $value, $parameters): bool
    {
        $value = $this->tryDecrypt($value);
        // exclude?
        $table   = $parameters[0];
        $field   = $parameters[1];
        $exclude = $parameters[2] ?? 0;

        // get entries from table
        $set = DB::table($table)->where('user_id', auth()->user()->id)
                 ->where('id', '!=', $exclude)->get([$field]);

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
    public function validateUniquePiggyBankForUser($attribute, $value, $parameters): bool
    {
        $exclude = $parameters[0] ?? null;
        $query   = DB::table('piggy_banks')->whereNull('piggy_banks.deleted_at')
                     ->leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')->where('accounts.user_id', auth()->user()->id);
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

    /**
     * @param int $index
     *
     * @return string
     */
    private function getRuleTriggerName($index): string
    {
        return $this->data['rule-trigger'][$index] ?? 'invalid';

    }

    /**
     * @param int $index
     *
     * @return string
     */
    private function getRuleTriggerValue($index): string
    {
        return $this->data['rule-trigger-value'][$index] ?? '';
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    private function tryDecrypt($value)
    {
        try {
            $value = Crypt::decrypt($value);
        } catch (DecryptException $e) {
            // do not care.
        }

        return $value;
    }

    /**
     * @return bool
     */
    private function validateAccountAnonymously(): bool
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
     * @return bool
     * @internal param $parameters
     *
     */
    private function validateByAccountId($value): bool
    {
        /** @var Account $existingAccount */
        $existingAccount = Account::find($this->data['id']);

        $type   = $existingAccount->accountType;
        $ignore = $existingAccount->id;
        $value  = $this->tryDecrypt($value);

        $set = auth()->user()->accounts()->where('account_type_id', $type->id)->where('id', '!=', $ignore)->get();
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
    private function validateByAccountTypeId($value, $parameters): bool
    {
        $type   = AccountType::find($this->data['account_type_id'])->first();
        $ignore = $parameters[0] ?? 0;
        $value  = $this->tryDecrypt($value);

        $set = auth()->user()->accounts()->where('account_type_id', $type->id)->where('id', '!=', $ignore)->get();
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
    private function validateByAccountTypeString($value, $parameters): bool
    {
        $search = Config::get('firefly.accountTypeByIdentifier.' . $this->data['what']);
        $type   = AccountType::whereType($search)->first();
        $ignore = $parameters[0] ?? 0;

        $set = auth()->user()->accounts()->where('account_type_id', $type->id)->where('id', '!=', $ignore)->get();
        /** @var Account $entry */
        foreach ($set as $entry) {
            if ($entry->name == $value) {
                return false;
            }
        }

        return true;
    }
}

