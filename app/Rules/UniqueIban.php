<?php

/**
 * UniqueIban.php
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

namespace FireflyIII\Rules;

use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Models\Account;
use FireflyIII\Support\Facades\Steam;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Class UniqueIban
 */
class UniqueIban implements ValidationRule
{
    private ?Account $account;
    private array    $expectedTypes;

    /**
     * Create a new rule instance.
     */
    public function __construct(?Account $account, ?string $expectedType)
    {
        $this->account       = $account;
        $this->expectedTypes = [];
        if (null === $expectedType) {
            return;
        }
        $this->expectedTypes = [$expectedType];
        // a very basic fix to make sure we get the correct account type:
        if ('expense' === $expectedType) {
            $this->expectedTypes = [AccountTypeEnum::EXPENSE->value];
        }
        if ('revenue' === $expectedType) {
            $this->expectedTypes = [AccountTypeEnum::REVENUE->value];
        }
        if ('asset' === $expectedType) {
            $this->expectedTypes = [AccountTypeEnum::ASSET->value];
        }
        if ('liabilities' === $expectedType) {
            $this->expectedTypes = [AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value];
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return (string) trans('validation.unique_iban_for_user');
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!$this->passes($attribute, $value)) {
            $fail((string) trans('validation.unique_iban_for_user'));
        }
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function passes($attribute, $value): bool
    {
        if (!auth()->check()) {
            return true;
        }
        if (0 === count($this->expectedTypes)) {
            return true;
        }
        $maxCounts = $this->getMaxOccurrences();

        foreach ($maxCounts as $type => $max) {
            // make sure to trim the value of $value so all spaces are removed.
            $value = Steam::filterSpaces($value);

            $count = $this->countHits($type, $value);
            app('log')->debug(sprintf('Count for "%s" and IBAN "%s" is %d', $type, $value, $count));
            if ($count > $max) {
                app('log')->debug(
                    sprintf(
                        'IBAN "%s" is in use with %d account(s) of type "%s", which is too much for expected types "%s"',
                        $value,
                        $count,
                        $type,
                        implode(', ', $this->expectedTypes)
                    )
                );

                return false;
            }
        }

        return true;
    }

    private function getMaxOccurrences(): array
    {
        $maxCounts = [
            AccountTypeEnum::ASSET->value   => 0,
            AccountTypeEnum::EXPENSE->value => 0,
            AccountTypeEnum::REVENUE->value => 0,
            'liabilities'                   => 0,
        ];

        if (in_array('expense', $this->expectedTypes, true) || in_array(AccountTypeEnum::EXPENSE->value, $this->expectedTypes, true)) {
            // IBAN should be unique amongst expense and asset accounts.
            // may appear once in revenue accounts
            $maxCounts[AccountTypeEnum::REVENUE->value] = 1;
        }
        if (in_array('revenue', $this->expectedTypes, true) || in_array(AccountTypeEnum::REVENUE->value, $this->expectedTypes, true)) {
            // IBAN should be unique amongst revenue and asset accounts.
            // may appear once in expense accounts
            $maxCounts[AccountTypeEnum::EXPENSE->value] = 1;
        }

        return $maxCounts;
    }

    private function countHits(string $type, string $iban): int
    {
        $typesArray = [$type];
        if ('liabilities' === $type) {
            $typesArray = [AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value];
        }
        $query
                    = auth()->user()
                        ->accounts()
                        ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                        ->where('accounts.iban', $iban)
                        ->whereIn('account_types.type', $typesArray)
        ;

        if (null !== $this->account) {
            $query->where('accounts.id', '!=', $this->account->id);
        }

        return $query->count();
    }
}
