<?php
/**
 * FireflyValidator.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Validation;

use Config;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Services\Password\Verifier;
use FireflyIII\TransactionRules\Triggers\TriggerInterface;
use FireflyIII\User;
use Google2FA;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

/**
 * Class FireflyValidator.
 */
class FireflyValidator extends Validator
{
    /**
     * @param $attribute
     * @param $value
     *
     * @return bool
     */
    public function validate2faCode($attribute, $value): bool
    {
        if (!\is_string($value) || null === $value || 6 !== strlen($value)) {
            return false;
        }

        $secret = session('two-factor-secret');

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

        if (0 === (int)$value) {
            return true;
        }
        $count = DB::table($parameters[0])->where('user_id', auth()->user()->id)->where($field, $value)->count();

        return 1 === $count;
    }

    /**
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
     * @param $attribute
     * @param $value
     *
     * @return bool
     */
    public function validateIban($attribute, $value): bool
    {
        if (!\is_string($value) || null === $value || strlen($value) < 6) {
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
     * @return bool
     */
    public function validateLess($attribute, $value, $parameters): bool
    {
        /** @var mixed $compare */
        $compare = $parameters[0] ?? '0';

        return bccomp((string)$value, (string)$compare) < 0;
    }

    /**
     * @param $attribute
     * @param $value
     * @param $parameters
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
     * @param string $attribute
     *
     * @param string $value
     *
     * @return bool
     */
    public function validateRuleActionValue(string $attribute, string $value = null): bool
    {
        // first, get the index from this string:
        $value = $value ?? '';
        $parts = explode('.', $attribute);
        $index = (int)($parts[1] ?? '0');

        // get the name of the trigger from the data array:
        $actionType = $this->data['actions'][$index]['type'] ?? 'invalid';

        // if it's "invalid" return false.
        if ('invalid' === $actionType) {
            return false;
        }

        // if it's set_budget, verify the budget name:
        if ('set_budget' === $actionType) {
            /** @var BudgetRepositoryInterface $repository */
            $repository = app(BudgetRepositoryInterface::class);
            $budgets    = $repository->getBudgets();
            // count budgets, should have at least one
            // TODO no longer need to loop like this
            $count = $budgets->filter(
                function (Budget $budget) use ($value) {
                    return $budget->name === $value;
                }
            )->count();

            return 1 === $count;
        }

        // if it's link to bill, verify the name of the bill.
        if ('link_to_bill' === $actionType) {
            /** @var BillRepositoryInterface $repository */
            $repository = app(BillRepositoryInterface::class);
            $bill       = $repository->findByName($value);

            return null !== $bill;
        }

        // if it's convert_transfer, it must be a valid asset account name.
        if ('convert_transfer' === $actionType) {
            /** @var AccountRepositoryInterface $repository */
            $repository = app(AccountRepositoryInterface::class);
            $account    = $repository->findByName(
                $value,
                [AccountType::DEFAULT, AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE,
                 AccountType::CREDITCARD]
            );

            return null !== $account;
        }

        // return true for the rest.
        return true;
    }

    /**
     * $attribute has the format triggers.%d.value.
     *
     * @param string $attribute
     * @param string $value
     *
     * @return bool
     */
    public function validateRuleTriggerValue(string $attribute, string $value = null): bool
    {
        // first, get the index from this string:
        $parts = explode('.', $attribute);
        $index = (int)($parts[1] ?? '0');

        // get the name of the trigger from the data array:
        $triggerType = $this->data['triggers'][$index]['type'] ?? 'invalid';

        // invalid always returns false:
        if ('invalid' === $triggerType) {
            return false;
        }

        // these trigger types need a numerical check:
        $numerical = ['amount_less', 'amount_more', 'amount_exactly'];
        if (in_array($triggerType, $numerical, true)) {
            return is_numeric($value);
        }

        // these trigger types need a simple strlen check:
        $length = ['from_account_starts', 'from_account_ends', 'from_account_is', 'from_account_contains', 'to_account_starts', 'to_account_ends',
                   'to_account_is', 'to_account_contains', 'description_starts', 'description_ends', 'description_contains', 'description_is', 'category_is',
                   'budget_is', 'tag_is', 'currency_is', 'notes_contain', 'notes_start', 'notes_end', 'notes_are',];
        if (in_array($triggerType, $length, true)) {
            return '' !== $value;
        }

        // check transaction type.
        if ('transaction_type' === $triggerType) {
            $count = TransactionType::where('type', ucfirst($value))->count();

            return 1 === $count;
        }

        // and finally a "will match everything check":
        $classes = app('config')->get('firefly.rule-triggers');
        /** @var TriggerInterface $class */
        $class = $classes[$triggerType] ?? false;
        if (false === $class) {
            return false;
        }

        return !$class::willMatchEverything($value);
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
        $parameterId = $parameters[0] ?? null;
        if (null !== $parameterId) {
            return $this->validateByParameterId((int)$parameterId, $value);
        }
        if (isset($this->data['id'])) {
            return $this->validateByAccountId($value);
        }

        // without type, just try to validate the name.
        return $this->validateByAccountName($value);
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
        $accountId = (int)($this->data['id'] ?? 0.0);
        if (0 === $accountId) {
            $accountId = (int)($parameters[0] ?? 0.0);
        }

        $query = AccountMeta::leftJoin('accounts', 'accounts.id', '=', 'account_meta.account_id')
                            ->whereNull('accounts.deleted_at')
                            ->where('accounts.user_id', auth()->user()->id)
                            ->where('account_meta.name', 'account_number');

        if ($accountId > 0) {
            // exclude current account from check.
            $query->where('account_meta.account_id', '!=', $accountId);
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
     *
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
        [$table, $field] = $parameters;
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
            $fieldValue = $entry->$field;

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
        if (null !== $exclude) {
            $query->where('piggy_banks.id', '!=', (int)$exclude);
        }
        $set = $query->get(['piggy_banks.*']);

        /** @var PiggyBank $entry */
        foreach ($set as $entry) {

            $fieldValue = $entry->name;
            if ($fieldValue === $value) {
                return false;
            }
        }

        return true;
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
        $value = $this->data['name'];

        $set = $user->accounts()->where('account_type_id', $type->id)->get();
        // TODO no longer need to loop like this
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

        /** @var Collection $set */
        $entry = auth()->user()->accounts()->where('account_type_id', $type->id)->where('id', '!=', $ignore)
                       ->where('name', $value)
                       ->first();

        return null === $entry;
    }


    /**
     * @param $value
     *
     * @return bool
     */
    private function validateByParameterId(int $accountId, $value): bool
    {
        /** @var Account $existingAccount */
        $existingAccount = Account::find($accountId);

        $type   = $existingAccount->accountType;
        $ignore = $existingAccount->id;

        /** @var Collection $set */
        $entry = auth()->user()->accounts()->where('account_type_id', $type->id)->where('id', '!=', $ignore)
                       ->where('name', $value)
                       ->first();

        return null === $entry;
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

        /** @var Collection $set */
        $set = auth()->user()->accounts()->where('account_type_id', $type->id)->where('id', '!=', $ignore)->get();
        // TODO no longer need to loop like this
        /** @var Account $entry */
        foreach ($set as $entry) {
            // TODO no longer need to loop like this.
            if ($entry->name === $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $value
     * @param array $parameters
     * @param string $type
     *
     * @return bool
     */
    private function validateByAccountTypeString(string $value, array $parameters, string $type): bool
    {
        /** @var array $search */
        $search = Config::get('firefly.accountTypeByIdentifier.' . $type);

        if (null === $search) {
            return false;
        }

        /** @var Collection $accountTypes */
        $accountTypes   = AccountType::whereIn('type', $search)->get();
        $ignore         = (int)($parameters[0] ?? 0.0);
        $accountTypeIds = $accountTypes->pluck('id')->toArray();
        /** @var Collection $set */
        $set = auth()->user()->accounts()->whereIn('account_type_id', $accountTypeIds)->where('id', '!=', $ignore)->get();
        // TODO no longer need to loop like this
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
     * @return bool
     */
    private function validateByAccountName(string $value): bool
    {
        return auth()->user()->accounts()->where('name', $value)->count() === 0;
    }
}
