<?php
/**
 * FireflyValidator.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

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
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Services\Password\Verifier;
use FireflyIII\TransactionRules\Triggers\TriggerInterface;
use FireflyIII\User;
use Google2FA;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Validation\Validator;

/**
 * Class FireflyValidator.
 */
class FireflyValidator extends Validator
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param $attribute
     * @param $value
     *
     * @return bool
     */
    public function validate2faCode($attribute, $value): bool
    {
        if (!\is_string($value) || null === $value || 6 !== \strlen($value)) {
            return false;
        }

        $secret = session('two-factor-secret');

        return Google2FA::verifyKey($secret, $value);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validateBelongsToUser($attribute, $value, $parameters): bool
    {
        $field = $parameters[1] ?? 'id';

        if (0 === (int)$value) {
            return true;
        }
        $count = DB::table($parameters[0])->where('user_id', auth()->user()->id)->where($field, $value)->count();

        return 1 === $count;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param $attribute
     * @param $value
     *
     * @return bool
     */
    public function validateBic($attribute, $value): bool
    {
        $regex  = '/^[a-z]{6}[0-9a-z]{2}([0-9a-z]{3})?\z/i';
        $result = preg_match($regex, $value);
        if (false === $result) {
            return false;
        }
        if (0 === $result) {
            return false;
        }

        return true;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param $attribute
     * @param $value
     *
     * @return bool
     */
    public function validateIban($attribute, $value): bool
    {
        if (!\is_string($value) || null === $value || \strlen($value) < 6) {
            return false;
        }
        // strip spaces
        $search  = [
            "\x20", // normal space
            "\u{0001}", // start of heading
            "\u{0002}", // start of text
            "\u{0003}", // end of text
            "\u{0004}", // end of transmission
            "\u{0005}", // enquiry
            "\u{0006}", // ACK
            "\u{0007}", // BEL
            "\u{0008}", // backspace
            "\u{000E}", // shift out
            "\u{000F}", // shift in
            "\u{0010}", // data link escape
            "\u{0011}", // DC1
            "\u{0012}", // DC2
            "\u{0013}", // DC3
            "\u{0014}", // DC4
            "\u{0015}", // NAK
            "\u{0016}", // SYN
            "\u{0017}", // ETB
            "\u{0018}", // CAN
            "\u{0019}", // EM
            "\u{001A}", // SUB
            "\u{001B}", // escape
            "\u{001C}", // file separator
            "\u{001D}", // group separator
            "\u{001E}", // record separator
            "\u{001F}", // unit separator
            "\u{007F}", // DEL
            "\u{00A0}", // non-breaking space
            "\u{1680}", // ogham space mark
            "\u{180E}", // mongolian vowel separator
            "\u{2000}", // en quad
            "\u{2001}", // em quad
            "\u{2002}", // en space
            "\u{2003}", // em space
            "\u{2004}", // three-per-em space
            "\u{2005}", // four-per-em space
            "\u{2006}", // six-per-em space
            "\u{2007}", // figure space
            "\u{2008}", // punctuation space
            "\u{2009}", // thin space
            "\u{200A}", // hair space
            "\u{200B}", // zero width space
            "\u{202F}", // narrow no-break space
            "\u{3000}", // ideographic space
            "\u{FEFF}", // zero width no -break space
        ];
        $replace = '';
        $value   = str_replace($search, $replace, $value);
        $value   = strtoupper($value);

        $search  = [' ', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $replace = ['', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31',
                    '32', '33', '34', '35',];

        // take
        $first    = substr($value, 0, 4);
        $last     = substr($value, 4);
        $iban     = $last . $first;
        $iban     = str_replace($search, $replace, $iban);
        $checksum = bcmod($iban, '97');

        return 1 === (int)$checksum;
    }

    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return bool
     */
    public function validateMore($attribute, $value, $parameters): bool
    {
        /** @var mixed $compare */
        $compare = $parameters[0] ?? '0';

        return bccomp((string)$value, (string)$compare) > 0;
    }

    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return bool
     */
    public function validateLess($attribute, $value, $parameters): bool
    {
        /** @var mixed $compare */
        $compare = $parameters[0] ?? '0';

        return bccomp((string)$value, (string)$compare) < 0;
    }


    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validateMustExist($attribute, $value, $parameters): bool
    {
        $field = $parameters[1] ?? 'id';

        if (0 === (int)$value) {
            return true;
        }
        $count = DB::table($parameters[0])->where($field, $value)->count();

        return 1 === $count;
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
        $index = $parts[\count($parts) - 1];
        if ($index === 'value') {
            // user is coming from API.
            $index = $parts[\count($parts) - 2];
        }
        $index = (int)$index;

        // get actions from $this->data
        $actions = [];
        if (isset($this->data['rule-action']) && \is_array($this->data['rule-action'])) {
            $actions = $this->data['rule-action'];
        }
        if (isset($this->data['rule-actions']) && \is_array($this->data['rule-actions'])) {
            $actions = $this->data['rule-actions'];
        }


        // loop all rule-actions.
        // check if rule-action-value matches the thing.
        if (\is_array($actions)) {
            $name  = $this->getRuleActionName($index);
            $value = $this->getRuleActionValue($index);
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
                            return $budget->name === $value;
                        }
                    )->count();

                    return 1 === $count;
                case 'link_to_bill':
                    /** @var BillRepositoryInterface $repository */
                    $repository = app(BillRepositoryInterface::class);
                    $bill       = $repository->findByName((string)$value);

                    return null !== $bill;
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
        $index = $parts[\count($parts) - 1];
        // if the index is not a number, then we might be dealing with an API $attribute
        // which is formatted "rule-triggers.0.value"
        if ($index === 'value') {
            $index = $parts[\count($parts) - 2];
        }
        $index = (int)$index;

        // get triggers from $this->data
        $triggers = [];
        if (isset($this->data['rule-trigger']) && \is_array($this->data['rule-trigger'])) {
            $triggers = $this->data['rule-trigger'];
        }
        if (isset($this->data['rule-triggers']) && \is_array($this->data['rule-triggers'])) {
            $triggers = $this->data['rule-triggers'];
        }

        // loop all rule-triggers.
        // check if rule-value matches the thing.
        if (\is_array($triggers)) {
            $name  = $this->getRuleTriggerName($index);
            $value = $this->getRuleTriggerValue($index);

            // break on some easy checks:
            switch ($name) {
                case 'amount_less':
                case 'amount_more':
                case 'amount_exactly':
                    $result = is_numeric($value);
                    if (false === $result) {
                        return false;
                    }
                    break;
                case 'from_account_starts':
                case 'from_account_ends':
                case 'from_account_is':
                case 'from_account_contains':
                case 'to_account_starts':
                case 'to_account_ends':
                case 'to_account_is':
                case 'to_account_contains':
                case 'description_starts':
                case 'description_ends':
                case 'description_contains':
                case 'description_is':
                case 'category_is':
                case 'budget_is':
                case 'tag_is':
                case 'currency_is':
                case 'notes_contain':
                case 'notes_start':
                case 'notes_end':
                case 'notes_are':
                    return \strlen($value) > 0;

                    break;
                case 'transaction_type':
                    $count = TransactionType::where('type', $value)->count();
                    if (!(1 === $count)) {
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

            return !$class::willMatchEverything($value);
        }

        return false;
    }

    /**
     * @param $attribute
     * @param $value
     *
     * @return bool
     */
    public function validateSecurePassword($attribute, $value): bool
    {
        $verify = false;
        if (isset($this->data['verify_password'])) {
            $verify = 1 === (int)$this->data['verify_password'];
        }
        if ($verify) {
            /** @var Verifier $service */
            $service = app(Verifier::class);

            return $service->validPassword($value);
        }

        return true;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param $attribute
     * @param $value
     * @param $parameters
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
            return $this->validateByAccountTypeString($value, $parameters, $this->data['what']);
        }
        if (isset($this->data['type'])) {
            return $this->validateByAccountTypeString($value, $parameters, $this->data['type']);
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validateUniqueAccountNumberForUser($attribute, $value, $parameters): bool
    {
        $accountId = (int)($this->data['id'] ?? 0.0);
        if ($accountId === 0) {
            $accountId = (int)($parameters[0] ?? 0.0);
        }

        $query = AccountMeta::leftJoin('accounts', 'accounts.id', '=', 'account_meta.account_id')
                            ->whereNull('accounts.deleted_at')
                            ->where('accounts.user_id', auth()->user()->id)
                            ->where('account_meta.name', 'accountNumber');

        if ((int)$accountId > 0) {
            // exclude current account from check.
            $query->where('account_meta.account_id', '!=', (int)$accountId);
        }
        $set = $query->get(['account_meta.*']);

        /** @var AccountMeta $entry */
        foreach ($set as $entry) {
            if ($entry->data === $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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
    public function validateUniqueObjectForUser($attribute, $value, $parameters): bool
    {
        $value = $this->tryDecrypt($value);
        // exclude?
        $table   = $parameters[0];
        $field   = $parameters[1];
        $exclude = (int)($parameters[2] ?? 0.0);

        /*
         * If other data (in $this->getData()) contains
         * ID field, set that field to be the $exclude.
         */
        $data = $this->getData();
        if (!isset($parameters[2]) && isset($data['id']) && (int)$data['id'] > 0) {
            $exclude = (int)$data['id'];
        }


        // get entries from table
        $set = DB::table($table)->where('user_id', auth()->user()->id)->whereNull('deleted_at')
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
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
        if (null !== $exclude) {
            $query->where('piggy_banks.id', '!=', (int)$exclude);
        }
        $set = $query->get(['piggy_banks.*']);

        /** @var PiggyBank $entry */
        foreach ($set as $entry) {

            $fieldValue = $this->tryDecrypt($entry->name);
            if ($fieldValue === $value) {
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
    private function getRuleActionName(int $index): string
    {
        $name = $this->data['rule-action'][$index] ?? 'invalid';
        if (!isset($this->data['rule-action'][$index])) {
            $name = $this->data['rule-actions'][$index]['name'] ?? 'invalid';
        }

        return $name;
    }

    /**
     * @param int $index
     *
     * @return string
     */
    private function getRuleActionValue(int $index): string
    {
        $value = $this->data['rule-action-value'][$index] ?? '';
        if (!isset($this->data['rule-action-value'][$index])) {
            $value = $this->data['rule-actions'][$index]['value'] ?? '';
        }

        return $value;
    }

    /**
     * @param int $index
     *
     * @return string
     */
    private function getRuleTriggerName(int $index): string
    {
        $name = $this->data['rule-trigger'][$index] ?? 'invalid';
        if (!isset($this->data['rule-trigger'][$index])) {
            $name = $this->data['rule-triggers'][$index]['name'] ?? 'invalid';
        }

        return $name;
    }

    /**
     * @param int $index
     *
     * @return string
     */
    private function getRuleTriggerValue(int $index): string
    {
        $value = $this->data['rule-trigger-value'][$index] ?? '';
        if (!isset($this->data['rule-trigger-value'][$index])) {
            $value = $this->data['rule-triggers'][$index]['value'] ?? '';
        }

        return $value;
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
            if ($entry->name === $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $value
     *
     * @return bool
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
            if ($entry->name === $value) {
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
        $ignore = (int)($parameters[0] ?? 0.0);
        $value  = $this->tryDecrypt($value);

        $set = auth()->user()->accounts()->where('account_type_id', $type->id)->where('id', '!=', $ignore)->get();
        /** @var Account $entry */
        foreach ($set as $entry) {
            if ($entry->name === $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $value
     * @param array  $parameters
     * @param string $type
     *
     * @return bool
     */
    private function validateByAccountTypeString(string $value, array $parameters, string $type): bool
    {
        $search      = Config::get('firefly.accountTypeByIdentifier.' . $type);
        $accountType = AccountType::whereType($search)->first();
        $ignore      = (int)($parameters[0] ?? 0.0);

        $set = auth()->user()->accounts()->where('account_type_id', $accountType->id)->where('id', '!=', $ignore)->get();
        /** @var Account $entry */
        foreach ($set as $entry) {
            if ($entry->name === $value) {
                return false;
            }
        }

        return true;
    }
}
