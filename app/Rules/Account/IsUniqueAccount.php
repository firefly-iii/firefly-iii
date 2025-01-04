<?php

/*
 * IsUniqueAccount.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Rules\Account;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\User;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * @method fail(string $string)
 */
class IsUniqueAccount implements ValidationRule, DataAwareRule
{
    protected array    $data = [];
    protected \Closure $fail;

    #[\Override]
    public function setData(array $data): self // @phpstan-ignore-line
    {
        $this->data = $data;

        return $this;
    }

    #[\Override]
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        return;
        $this->fail  = $fail;
        // because a user does not have to be logged in (tests and what-not).
        if (!auth()->check()) {
            app('log')->debug('validateUniqueAccountForUser::anon');
            $fail('validation.nog_logged_in')->translate();

            return;
        }
        if (array_key_exists('type', $this->data)) {
            app('log')->debug('validateUniqueAccountForUser::typeString');

            $this->validateByAccountTypeString($value, $parameters, (string) $this->data['type']);
        }
        if (array_key_exists('account_type_id', $this->data)) {
            app('log')->debug('validateUniqueAccountForUser::typeId');

            $this->validateByAccountTypeId($value, $parameters);
        }
        $parameterId = $parameters[0] ?? null;
        if (null !== $parameterId) {
            app('log')->debug('validateUniqueAccountForUser::paramId');

            $this->validateByParameterId((int) $parameterId, $value);
        }
        if (array_key_exists('id', $this->data)) {
            app('log')->debug('validateUniqueAccountForUser::accountId');

            $this->validateByAccountId($value);
        }

        // without type, just try to validate the name.
        app('log')->debug('validateUniqueAccountForUser::accountName');

        $this->validateByAccountName($value);
    }

    /**
     * TODO duplicate from old validation class.
     */
    private function validateByAccountTypeString(string $value, array $parameters, string $type): bool
    {
        /** @var null|array $search */
        $search         = config('firefly.accountTypeByIdentifier.%s', $type);

        if (null === $search) {
            return false;
        }

        $accountTypes   = AccountType::whereIn('type', $search)->get();
        $ignore         = (int) ($parameters[0] ?? 0.0);
        $accountTypeIds = $accountTypes->pluck('id')->toArray();

        /** @var null|Account $result */
        $result         = auth()->user()->accounts()->whereIn('account_type_id', $accountTypeIds)->where('id', '!=', $ignore)
            ->where('name', $value)
            ->first()
        ;

        return null === $result;
    }

    /**
     * TODO Duplicate from old validation class.
     *
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
            ->first()
        ;

        return null === $result;
    }

    /**
     * TODO Duplicate from old validation class.
     *
     * @param mixed $value
     */
    private function validateByParameterId(int $accountId, $value): bool
    {
        /** @var Account $existingAccount */
        $existingAccount = Account::find($accountId);

        $type            = $existingAccount->accountType;
        $ignore          = $existingAccount->id;

        $entry           = auth()->user()->accounts()->where('account_type_id', $type->id)->where('id', '!=', $ignore)
            ->where('name', $value)
            ->first()
        ;

        return null === $entry;
    }

    /**
     * TODO Duplicate from old validation class.
     *
     * @param mixed $value
     */
    private function validateByAccountId($value): bool
    {
        /** @var Account $existingAccount */
        $existingAccount = Account::find($this->data['id']);

        $type            = $existingAccount->accountType;
        $ignore          = $existingAccount->id;

        $entry           = auth()->user()->accounts()->where('account_type_id', $type->id)->where('id', '!=', $ignore)
            ->where('name', $value)
            ->first()
        ;

        return null === $entry;
    }

    /**
     * TODO is duplicate
     * TODO does not take group into account. Must be made group aware.
     */
    private function validateByAccountName(string $value): bool
    {
        return 0 === auth()->user()->accounts()->where('name', $value)->count();
    }

    /**
     * TODO duplicate from old validation class.
     */
    private function validateAccountAnonymously(): bool
    {
        if (!array_key_exists('user_id', $this->data)) {
            $this->fail('No user ID provided.');

            return false;
        }

        /** @var User $user */
        $user   = User::find($this->data['user_id']);
        $type   = AccountType::find($this->data['account_type_id'])->first();
        $value  = $this->data['name'];

        /** @var null|Account $result */
        $result = $user->accounts()->where('account_type_id', $type->id)->where('name', $value)->first();

        return null === $result;
    }
}
