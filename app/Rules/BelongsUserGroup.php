<?php

/*
 * BelongsUserGroup.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Rules;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\UserGroup;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Class BelongsUserGroup
 * TODO this method has a lot in common with BelongsUser but will check if the UserGroup
 * TODO that is submitted is valid. This method will not validate if the user has a valid ROLE in this
 * TODO group.
 */
class BelongsUserGroup implements ValidationRule
{
    private UserGroup $userGroup;

    /**
     * Create a new rule instance.
     */
    public function __construct(UserGroup $userGroup)
    {
        $this->userGroup = $userGroup;
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $attribute = $this->parseAttribute($attribute);
        if (!auth()->check()) {
            $fail('validation.belongs_user_or_user_group')->translate();

            return;
        }
        app('log')->debug(sprintf('Group: Going to validate "%s"', $attribute));

        $result    = match ($attribute) {
            'piggy_bank_id'               => $this->validatePiggyBankId((int) $value),
            'piggy_bank_name'             => $this->validatePiggyBankName($value),
            'bill_id'                     => $this->validateBillId((int) $value),
            'transaction_journal_id'      => $this->validateJournalId((int) $value),
            'bill_name'                   => $this->validateBillName($value),
            'budget_id'                   => $this->validateBudgetId((int) $value),
            'category_id'                 => $this->validateCategoryId((int) $value),
            'budget_name'                 => $this->validateBudgetName($value),
            'source_id', 'destination_id' => $this->validateAccountId((int) $value),
            default                       => throw new FireflyException(sprintf('Rule BelongsUser cannot handle "%s"', $attribute)),
        };
        if (false === $result) {
            $fail('validation.belongs_user_or_user_group')->translate();
        }
    }

    private function parseAttribute(string $attribute): string
    {
        $parts = explode('.', $attribute);
        if (1 === count($parts)) {
            return $attribute;
        }
        if (3 === count($parts)) {
            return $parts[2];
        }

        return $attribute;
    }

    private function validatePiggyBankId(int $value): bool
    {
        $count = PiggyBank::leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')
            ->where('piggy_banks.id', '=', $value)
            ->where('accounts.user_group_id', '=', $this->userGroup->id)->count()
        ;

        return 1 === $count;
    }

    private function validatePiggyBankName(string $value): bool
    {
        $count = $this->countField(PiggyBank::class, 'name', $value);

        return 1 === $count;
    }

    protected function countField(string $class, string $field, string $value): int
    {
        $value   = trim($value);
        $objects = [];
        // get all objects belonging to user:
        if (PiggyBank::class === $class) {
            $objects = PiggyBank::leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')
                ->where('accounts.user_group_id', '=', $this->userGroup->id)->get(['piggy_banks.*'])
            ;
        }
        if (PiggyBank::class !== $class) {
            $objects = $class::where('user_group_id', '=', $this->userGroup->id)->get();
        }
        $count   = 0;
        foreach ($objects as $object) {
            $objectValue = trim((string) $object->{$field}); // @phpstan-ignore-line
            app('log')->debug(sprintf('Comparing object "%s" with value "%s"', $objectValue, $value));
            if ($objectValue === $value) {
                ++$count;
                app('log')->debug(sprintf('Hit! Count is now %d', $count));
            }
        }

        return $count;
    }

    private function validateBillId(int $value): bool
    {
        if (0 === $value) {
            return true;
        }
        $count = Bill::where('id', '=', $value)->where('user_group_id', '=', $this->userGroup->id)->count();

        return 1 === $count;
    }

    private function validateJournalId(int $value): bool
    {
        if (0 === $value) {
            return true;
        }
        $count = TransactionJournal::where('id', '=', $value)->where('user_group_id', '=', $this->userGroup->id)->count();

        return 1 === $count;
    }

    private function validateBillName(string $value): bool
    {
        $count = $this->countField(Bill::class, 'name', $value);
        app('log')->debug(sprintf('Result of countField for bill name "%s" is %d', $value, $count));

        return 1 === $count;
    }

    private function validateBudgetId(int $value): bool
    {
        if (0 === $value) {
            return true;
        }
        $count = Budget::where('id', '=', $value)->where('user_group_id', '=', $this->userGroup->id)->count();

        return 1 === $count;
    }

    private function validateCategoryId(int $value): bool
    {
        $count = Category::where('id', '=', $value)->where('user_group_id', '=', $this->userGroup->id)->count();

        return 1 === $count;
    }

    private function validateBudgetName(string $value): bool
    {
        $count = $this->countField(Budget::class, 'name', $value);

        return 1 === $count;
    }

    private function validateAccountId(int $value): bool
    {
        if (0 === $value) {
            // it's ok to submit 0. other checks will fail.
            return true;
        }
        $count = Account::where('id', '=', $value)->where('user_group_id', '=', $this->userGroup->id)->count();

        return 1 === $count;
    }
}
