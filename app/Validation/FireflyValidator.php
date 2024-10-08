<?php

/**
 * FireflyValidator.php
 * Copyright (c) 2019 james@firefly-iii.org
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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionType;
use FireflyIII\Models\Webhook;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Services\Password\Verifier;
use FireflyIII\Support\ParseDateString;
use FireflyIII\User;
use Illuminate\Validation\Validator;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;

/**
 * Class FireflyValidator.
 * TODO all of these validations must become separate classes.
 */
class FireflyValidator extends Validator
{
    /**
     * @param mixed $attribute
     * @param mixed $value
     *
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate2faCode($attribute, $value): bool
    {
        if (!is_string($value) || 6 !== strlen($value)) {
            return false;
        }
        $user = auth()->user();
        if (null === $user) {
            app('log')->error('No user during validate2faCode');

            return false;
        }
        $secretPreference = app('preferences')->get('temp-mfa-secret');
        $secret           = $secretPreference?->data ?? '';
        if (is_array($secret)) {
            $secret = '';
        }
        $secret = (string) $secret;

        return (bool) \Google2FA::verifyKey((string) $secret, $value);
    }
public function validateExistingMfaCode($attribute, $value): bool
{
    if (!is_string($value) || 6 !== strlen($value)) {
        return false;
    }
    $user = auth()->user();
    if (null === $user) {
        app('log')->error('No user during validate2faCode');

        return false;
    }
    $secret = (string)$user->mfa_secret;

    return (bool) \Google2FA::verifyKey($secret, $value);
}

    /**
     * @param mixed $attribute
     * @param mixed $value
     * @param mixed $parameters
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateBelongsToUser($attribute, $value, $parameters): bool
    {
        $field = $parameters[1] ?? 'id';

        if (0 === (int) $value) {
            return true;
        }
        $count = \DB::table($parameters[0])->where('user_id', auth()->user()->id)->where($field, $value)->count();

        return 1 === $count;
    }

    /**
     * @param mixed $attribute
     * @param mixed $value
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateBic($attribute, $value): bool
    {
        $regex  = '/^[a-z]{6}[0-9a-z]{2}([0-9a-z]{3})?\z/i';
        $result = preg_match($regex, $value);
        if (false === $result || 0 === $result) {
            return false;
        }

        return true;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function validateIban(mixed $attribute, mixed $value): bool
    {
        if (!is_string($value) || strlen($value) < 6) {
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
            ' ',
            '-',
            '?',
        ];
        $replace = '';
        $value   = str_replace($search, $replace, $value);
        $value   = strtoupper($value);

        // replace characters outside of ASCI range.
        $value   = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $search  = [' ', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $replace = ['', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35'];

        // take
        $first = substr($value, 0, 4);
        $last  = substr($value, 4);
        $iban  = $last . $first;
        $iban  = trim(str_replace($search, $replace, $iban));
        if ('' === $iban) {
            return false;
        }

        try {
            $checksum = bcmod($iban, '97');
        } catch (\ValueError $e) { // @phpstan-ignore-line
            $message = sprintf('Could not validate IBAN check value "%s" (IBAN "%s")', $iban, $value);
            app('log')->error($message);
            app('log')->error($e->getTraceAsString());

            return false;
        }

        return 1 === (int) $checksum;
    }

    /**
     * @param mixed $attribute
     * @param mixed $value
     * @param mixed $parameters
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateLess($attribute, $value, $parameters): bool
    {
        /** @var mixed $compare */
        $compare = $parameters[0] ?? '0';

        return bccomp((string) $value, (string) $compare) < 0;
    }

    /**
     * @param mixed $attribute
     * @param mixed $value
     * @param mixed $parameters
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateMore($attribute, $value, $parameters): bool
    {
        /** @var mixed $compare */
        $compare = $parameters[0] ?? '0';

        return bccomp((string) $value, (string) $compare) > 0;
    }

    /**
     * @param mixed $attribute
     * @param mixed $value
     * @param mixed $parameters
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateMustExist($attribute, $value, $parameters): bool
    {
        $field = $parameters[1] ?? 'id';

        if (0 === (int) $value) {
            return true;
        }
        $count = \DB::table($parameters[0])->where($field, $value)->count();

        return 1 === $count;
    }

    public function validateRuleActionValue(string $attribute, ?string $value = null): bool
    {
        // first, get the index from this string:
        $value ??= '';
        $parts = explode('.', $attribute);
        $index = (int) ($parts[1] ?? '0');

        // get the name of the trigger from the data array:
        $actionType = $this->data['actions'][$index]['type'] ?? 'invalid';

        // if it's "invalid" return false.
        if ('invalid' === $actionType) {
            return false;
        }

        // if value is an expression, assume valid
        if (true === config('firefly.feature_flags.expression_engine') && str_starts_with($value, '=') && strlen($value) > 1) {
            return true;
        }

        // if it's set_budget, verify the budget name:
        if ('set_budget' === $actionType) {
            /** @var BudgetRepositoryInterface $repository */
            $repository = app(BudgetRepositoryInterface::class);

            return null !== $repository->findByName($value);
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
                [
                    AccountType::DEFAULT,
                    AccountType::ASSET,
                    AccountType::LOAN,
                    AccountType::DEBT,
                    AccountType::MORTGAGE,
                    AccountType::CREDITCARD,
                ]
            );

            return null !== $account;
        }

        if ('update_piggy' === $actionType) {
            /** @var PiggyBankRepositoryInterface $repository */
            $repository = app(PiggyBankRepositoryInterface::class);
            $piggy      = $repository->findByName($value);

            return null !== $piggy;
        }

        // return true for the rest.
        return true;
    }

    /**
     * $attribute has the format triggers.%d.value.
     */
    public function validateRuleTriggerValue(string $attribute, ?string $value = null): bool
    {
        // first, get the index from this string:
        $parts = explode('.', $attribute);
        $index = (int) ($parts[1] ?? '0');

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

        // these triggers need just the word "true":
        // TODO create a helper to automatically return these.
        $needTrue = [
            'reconciled', 'has_attachments', 'has_any_category', 'has_any_budget', 'has_any_bill', 'has_any_tag', 'any_notes', 'any_external_url', 'has_no_attachments', 'has_no_category', 'has_no_budget', 'has_no_bill', 'has_no_tag', 'no_notes', 'no_external_url',
            'source_is_cash',
            'destination_is_cash',
            'account_is_cash',
            'exists',
            'no_external_id',
            'any_external_id',
        ];
        if (in_array($triggerType, $needTrue, true)) {
            return 'true' === $value;
        }

        // these trigger types need a simple strlen check:
        // TODO create a helper to automatically return these.
        $length = [
            'source_account_starts',
            'source_account_ends',
            'source_account_is',
            'source_account_contains',
            'destination_account_starts',
            'destination_account_ends',
            'destination_account_is',
            'destination_account_contains',
            'description_starts',
            'description_ends',
            'description_contains',
            'description_is',
            'category_is',
            'budget_is',
            'tag_is',
            'currency_is',
            'notes_contain',
            'notes_start',
            'notes_end',
            'notes_are',
        ];
        if (in_array($triggerType, $length, true)) {
            return '' !== $value;
        }

        // check if it's an existing account.
        // TODO create a helper to automatically return these.
        if (in_array($triggerType, ['destination_account_id', 'source_account_id'], true)) {
            return is_numeric($value) && (int) $value > 0;
        }

        // check transaction type.
        // TODO create a helper to automatically return these.
        if ('transaction_type' === $triggerType) {
            $count = TransactionType::where('type', ucfirst($value))->count();

            return 1 === $count;
        }

        // if the type is date, then simply try to parse it and throw error when it's bad.
        // TODO create a helper to automatically return these.
        if (in_array($triggerType, ['date_is', 'created_on', 'updated_on', 'date_before', 'date_after'], true)) {
            /** @var ParseDateString $parser */
            $parser = app(ParseDateString::class);

            try {
                $parser->parseDate($value);
            } catch (FireflyException $e) {
                app('log')->error($e->getMessage());

                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed $attribute
     * @param mixed $value
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateSecurePassword($attribute, $value): bool
    {
        $verify = false;
        if (array_key_exists('verify_password', $this->data)) {
            $verify = 1 === (int) $this->data['verify_password'];
        }
        if ($verify) {
            /** @var Verifier $service */
            $service = app(Verifier::class);

            return $service->validPassword($value);
        }

        return true;
    }

    /**
     * @param mixed $attribute
     * @param mixed $value
     * @param mixed $parameters
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateUniqueAccountForUser($attribute, $value, $parameters): bool
    {
        // because a user does not have to be logged in (tests and what-not).
        if (!auth()->check()) {
            app('log')->debug('validateUniqueAccountForUser::anon');

            return $this->validateAccountAnonymously();
        }
        if (array_key_exists('objectType', $this->data)) {
            app('log')->debug('validateUniqueAccountForUser::typeString');

            return $this->validateByAccountTypeString($value, $parameters, $this->data['objectType']);
        }
        if (array_key_exists('type', $this->data)) {
            app('log')->debug('validateUniqueAccountForUser::typeString');

            return $this->validateByAccountTypeString($value, $parameters, (string) $this->data['type']);
        }
        if (array_key_exists('account_type_id', $this->data)) {
            app('log')->debug('validateUniqueAccountForUser::typeId');

            return $this->validateByAccountTypeId($value, $parameters);
        }
        $parameterId = $parameters[0] ?? null;
        if (null !== $parameterId) {
            app('log')->debug('validateUniqueAccountForUser::paramId');

            return $this->validateByParameterId((int) $parameterId, $value);
        }
        if (array_key_exists('id', $this->data)) {
            app('log')->debug('validateUniqueAccountForUser::accountId');

            return $this->validateByAccountId($value);
        }

        // without type, just try to validate the name.
        app('log')->debug('validateUniqueAccountForUser::accountName');

        return $this->validateByAccountName($value);
    }

    private function validateAccountAnonymously(): bool
    {
        if (!array_key_exists('user_id', $this->data)) {
            return false;
        }

        /** @var User $user */
        $user  = User::find($this->data['user_id']);
        $type  = AccountType::find($this->data['account_type_id'])->first();
        $value = $this->data['name'];

        /** @var null|Account $result */
        $result = $user->accounts()->where('account_type_id', $type->id)->where('name', $value)->first();

        return null === $result;
    }

    private function validateByAccountTypeString(string $value, array $parameters, string $type): bool
    {
        /** @var null|array $search */
        $search = \Config::get('firefly.accountTypeByIdentifier.' . $type);

        if (null === $search) {
            return false;
        }

        $accountTypes   = AccountType::whereIn('type', $search)->get();
        $ignore         = (int) ($parameters[0] ?? 0.0);
        $accountTypeIds = $accountTypes->pluck('id')->toArray();

        /** @var null|Account $result */
        $result = auth()->user()->accounts()->whereIn('account_type_id', $accountTypeIds)->where('id', '!=', $ignore)
                        ->where('name', $value)
                        ->first();

        return null === $result;
    }

    /**
     * @param mixed $value
     * @param mixed $parameters
     */
    private function validateByAccountTypeId($value, $parameters): bool
    {
        $type   = AccountType::find($this->data['account_type_id'])->first();
        $ignore = (int) ($parameters[0] ?? 0.0);

        /** @var null|Account $result */
        $result = auth()->user()->accounts()->where('account_type_id', $type->id)->where('id', '!=', $ignore)
                        ->where('name', $value)
                        ->first();

        return null === $result;
    }

    /**
     * @param mixed $value
     */
    private function validateByParameterId(int $accountId, $value): bool
    {
        /** @var Account $existingAccount */
        $existingAccount = Account::find($accountId);

        $type   = $existingAccount->accountType;
        $ignore = $existingAccount->id;

        $entry = auth()->user()->accounts()->where('account_type_id', $type->id)->where('id', '!=', $ignore)
                       ->where('name', $value)
                       ->first();

        return null === $entry;
    }

    /**
     * @param mixed $value
     */
    private function validateByAccountId($value): bool
    {
        /** @var Account $existingAccount */
        $existingAccount = Account::find($this->data['id']);

        $type   = $existingAccount->accountType;
        $ignore = $existingAccount->id;

        $entry = auth()->user()->accounts()->where('account_type_id', $type->id)->where('id', '!=', $ignore)
                       ->where('name', $value)
                       ->first();

        return null === $entry;
    }

    private function validateByAccountName(string $value): bool
    {
        return 0 === auth()->user()->accounts()->where('name', $value)->count();
    }

    /**
     * @param mixed $attribute
     * @param mixed $value
     * @param mixed $parameters
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateUniqueAccountNumberForUser($attribute, $value, $parameters): bool
    {
        $accountId = (int) ($this->data['id'] ?? 0.0);
        if (0 === $accountId) {
            $accountId = (int) ($parameters[0] ?? 0.0);
        }

        $query = AccountMeta::leftJoin('accounts', 'accounts.id', '=', 'account_meta.account_id')
                            ->whereNull('accounts.deleted_at')
                            ->where('accounts.user_id', auth()->user()->id)
                            ->where('account_meta.name', 'account_number')
                            ->where('account_meta.data', json_encode($value));

        if ($accountId > 0) {
            // exclude current account from check.
            $query->where('account_meta.account_id', '!=', $accountId);
        }
        $set   = $query->get(['account_meta.*']);
        $count = $set->count();
        if (0 === $count) {
            return true;
        }
        if ($count > 1) {
            // pretty much impossible but still.
            return false;
        }
        $type = $this->data['objectType'] ?? 'unknown';
        if ('expense' !== $type && 'revenue' !== $type) {
            app('log')->warning(sprintf('Account number "%s" is not unique and account type "%s" cannot share its account number.', $value, $type));

            return false;
        }
        app('log')->debug(sprintf('Account number "%s" is not unique but account type "%s" may share its account number.', $value, $type));

        // one other account with this account number.
        /** @var AccountMeta $entry */
        foreach ($set as $entry) {
            $otherAccount = $entry->account;
            $otherType    = (string) config(sprintf('firefly.shortNamesByFullName.%s', $otherAccount->accountType->type));
            if (('expense' === $otherType || 'revenue' === $otherType) && $otherType !== $type) {
                app('log')->debug(sprintf('The other account with this account number is a "%s" so return true.', $otherType));

                return true;
            }
            app('log')->debug(sprintf('The other account with this account number is a "%s" so return false.', $otherType));
        }

        return false;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateUniqueCurrencyCode(?string $attribute, ?string $value): bool
    {
        return $this->validateUniqueCurrency('code', (string) $attribute, (string) $value);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateUniqueCurrency(string $field, string $attribute, string $value): bool
    {
        return 0 === \DB::table('transaction_currencies')->where($field, $value)->whereNull('deleted_at')->count();
    }

    public function validateUniqueCurrencyName(?string $attribute, ?string $value): bool
    {
        return $this->validateUniqueCurrency('name', (string) $attribute, (string) $value);
    }

    public function validateUniqueCurrencySymbol(?string $attribute, ?string $value): bool
    {
        return $this->validateUniqueCurrency('symbol', (string) $attribute, (string) $value);
    }

    /**
     * @param mixed $value
     * @param mixed $parameters
     * @param mixed $something
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateUniqueExistingWebhook($value, $parameters, $something): bool
    {
        $existingId = (int) ($something[0] ?? 0);
        $trigger    = 0;
        $response   = 0;
        $delivery   = 0;
        $triggers   = Webhook::getTriggersForValidation();
        $responses  = Webhook::getResponsesForValidation();
        $deliveries = Webhook::getDeliveriesForValidation();
        if (auth()->check()) {
            // get existing webhook value:
            if (0 !== $existingId) {
                /** @var null|Webhook $webhook */
                $webhook = auth()->user()->webhooks()->find($existingId);
                if (null === $webhook) {
                    return false;
                }
                // set triggers etc.
                $trigger  = $triggers[$webhook->trigger] ?? 0;
                $response = $responses[$webhook->response] ?? 0;
                $delivery = $deliveries[$webhook->delivery] ?? 0;
            }
            if (0 === $existingId) {
                $trigger  = $triggers[$this->data['trigger']] ?? 0;
                $response = $responses[$this->data['response']] ?? 0;
                $delivery = $deliveries[$this->data['delivery']] ?? 0;
            }
            $url    = $this->data['url'];
            $userId = auth()->user()->id;

            return 0 === Webhook::whereUserId($userId)
                                ->where('trigger', $trigger)
                                ->where('response', $response)
                                ->where('delivery', $delivery)
                                ->where('id', '!=', $existingId)
                                ->where('url', $url)->count();
        }

        return false;
    }

    /**
     * Validate an object and its uniqueness. Checks for encryption / encrypted values as well.
     *
     * parameter 0: the table
     * parameter 1: the field
     * parameter 2: an id to ignore (when editing)
     *
     * @param mixed $attribute
     * @param mixed $value
     * @param mixed $parameters
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateUniqueObjectForUser($attribute, $value, $parameters): bool
    {
        [$table, $field] = $parameters;
        $exclude = (int) ($parameters[2] ?? 0.0);

        /*
         * If other data (in $this->getData()) contains
         * ID field, set that field to be the $exclude.
         */
        $data = $this->getData();
        if (!array_key_exists(2, $parameters) && array_key_exists('id', $data) && (int) $data['id'] > 0) {
            $exclude = (int) $data['id'];
        }
        // get entries from table
        $result = \DB::table($table)->where('user_id', auth()->user()->id)->whereNull('deleted_at')
                     ->where('id', '!=', $exclude)
                     ->where($field, $value)
                     ->first([$field]);
        if (null === $result) {
            return true; // not found, so true.
        }

        // found, so not unique.
        return false;
    }

    /**
     * @param mixed $attribute
     * @param mixed $value
     * @param mixed $parameters
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateUniqueObjectGroup($attribute, $value, $parameters): bool
    {
        $exclude = $parameters[0] ?? null;
        $query   = \DB::table('object_groups')
                      ->whereNull('object_groups.deleted_at')
                      ->where('object_groups.user_id', auth()->user()->id)
                      ->where('object_groups.title', $value);
        if (null !== $exclude) {
            $query->where('object_groups.id', '!=', (int) $exclude);
        }

        return 0 === $query->count();
    }

    /**
     * @param mixed $attribute
     * @param mixed $value
     * @param mixed $parameters
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateUniquePiggyBankForUser($attribute, $value, $parameters): bool
    {
        $exclude = $parameters[0] ?? null;
        $query   = \DB::table('piggy_banks')->whereNull('piggy_banks.deleted_at')
                      ->leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')->where('accounts.user_id', auth()->user()->id);
        if (null !== $exclude) {
            $query->where('piggy_banks.id', '!=', (int) $exclude);
        }
        $query->where('piggy_banks.name', $value);

        return null === $query->first(['piggy_banks.*']);
    }

    /**
     * @param mixed $value
     * @param mixed $parameters
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateUniqueWebhook($value, $parameters): bool
    {
        if (auth()->check()) {
            $triggers   = Webhook::getTriggersForValidation();
            $responses  = Webhook::getResponsesForValidation();
            $deliveries = Webhook::getDeliveriesForValidation();

            // integers
            $trigger  = $triggers[$this->data['trigger']] ?? 0;
            $response = $responses[$this->data['response']] ?? 0;
            $delivery = $deliveries[$this->data['delivery']] ?? 0;
            $url      = $this->data['url'];
            $userId   = auth()->user()->id;

            return 0 === Webhook::whereUserId($userId)
                                ->where('trigger', $trigger)
                                ->where('response', $response)
                                ->where('delivery', $delivery)
                                ->where('url', $url)->count();
        }

        return false;
    }
}
