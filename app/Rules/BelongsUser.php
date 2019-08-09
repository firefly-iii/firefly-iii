<?php

/**
 * BelongsUser.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Rules;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\PiggyBank;
use Illuminate\Contracts\Validation\Rule;
use Log;

/**
 * Class BelongsUser
 */
class BelongsUser implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the validation error message.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function message(): string
    {
        return (string)trans('validation.belongs_user');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed  $value
     *
     * @return bool
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function passes($attribute, $value): bool
    {
        $attribute = $this->parseAttribute($attribute);
        if (!auth()->check()) {
            return true; // @codeCoverageIgnore
        }
        $attribute = (string)$attribute;
        Log::debug(sprintf('Going to validate %s', $attribute));
        switch ($attribute) {
            case 'piggy_bank_id':
                return $this->validatePiggyBankId((int)$value);
            case 'piggy_bank_name':
                return $this->validatePiggyBankName($value);
            case 'bill_id':
                return $this->validateBillId((int)$value);
            case 'bill_name':
                return $this->validateBillName($value);
            case 'budget_id':
                return $this->validateBudgetId((int)$value);
            case 'category_id':
                return $this->validateCategoryId((int)$value);
            case 'budget_name':
                return $this->validateBudgetName($value);
            case 'source_id':
            case 'destination_id':
                return $this->validateAccountId((int)$value);
            default:
                throw new FireflyException(sprintf('Rule BelongUser cannot handle "%s"', $attribute)); // @codeCoverageIgnore
        }
    }

    /**
     * @param string $class
     * @param string $field
     * @param string $value
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function countField(string $class, string $field, string $value): int
    {
        $value   = trim($value);
        $objects = [];
        // get all objects belonging to user:
        if (PiggyBank::class === $class) {
            $objects = PiggyBank::leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')
                                ->where('accounts.user_id', '=', auth()->user()->id)->get(['piggy_banks.*']);

        }
        if (PiggyBank::class !== $class) {
            $objects = $class::where('user_id', '=', auth()->user()->id)->get();
        }
        $count = 0;
        foreach ($objects as $object) {
            $objectValue = trim((string)$object->$field);
            Log::debug(sprintf('Comparing object "%s" with value "%s"', $objectValue, $value));
            if ($objectValue === $value) {
                $count++;
                Log::debug(sprintf('Hit! Count is now %d', $count));
            }
        }

        return $count;
    }

    /**
     * @param string $attribute
     *
     * @return string
     */
    private function parseAttribute(string $attribute): string
    {
        $parts = explode('.', $attribute);
        if (1 === count($parts)) {
            return $attribute;
        }
        if (3 === count($parts)) {
            return $parts[2];
        }

        return $attribute; // @codeCoverageIgnore
    }

    /**
     * @param int $value
     *
     * @return bool
     */
    private function validateAccountId(int $value): bool
    {
        $count = Account::where('id', '=', $value)->where('user_id', '=', auth()->user()->id)->count();

        return 1 === $count;
    }

    /**
     * @param int $value
     *
     * @return bool
     */
    private function validateBillId(int $value): bool
    {
        $count = Bill::where('id', '=', $value)->where('user_id', '=', auth()->user()->id)->count();

        return 1 === $count;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    private function validateBillName(string $value): bool
    {
        $count = $this->countField(Bill::class, 'name', $value);
        Log::debug(sprintf('Result of countField for bill name "%s" is %d', $value, $count));

        return 1 === $count;
    }

    /**
     * @param int $value
     *
     * @return bool
     */
    private function validateBudgetId(int $value): bool
    {
        $count = Budget::where('id', '=', $value)->where('user_id', '=', auth()->user()->id)->count();

        return 1 === $count;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    private function validateBudgetName(string $value): bool
    {
        $count = $this->countField(Budget::class, 'name', $value);

        return 1 === $count;
    }

    /**
     * @param int $value
     *
     * @return bool
     */
    private function validateCategoryId(int $value): bool
    {
        $count = Category::where('id', '=', $value)->where('user_id', '=', auth()->user()->id)->count();

        return 1 === $count;
    }

    /**
     * @param int $value
     *
     * @return bool
     */
    private function validatePiggyBankId(int $value): bool
    {
        $count = PiggyBank::leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')
                          ->where('piggy_banks.id', '=', $value)
                          ->where('accounts.user_id', '=', auth()->user()->id)->count();

        return 1 === $count;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    private function validatePiggyBankName(string $value): bool
    {
        $count = $this->countField(PiggyBank::class, 'name', $value);

        return 1 === $count;
    }
}
