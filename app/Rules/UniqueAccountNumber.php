<?php

/**
 * UniqueAccountNumber.php
 * Copyright (c) 2021 james@firefly-iii.org
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
use FireflyIII\Models\AccountMeta;
use Illuminate\Contracts\Validation\ValidationRule;
use Closure;

use function Safe\json_encode;

/**
 * Class UniqueAccountNumber
 */
class UniqueAccountNumber implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(private readonly ?Account $account, private ?string $expectedType)
    {
        app('log')
            ->debug('Constructed UniqueAccountNumber')
        ;
        // a very basic fix to make sure we get the correct account type:
        if ('expense' === $this->expectedType) {
            $this->expectedType = AccountTypeEnum::EXPENSE->value;
        }
        if ('revenue' === $this->expectedType) {
            $this->expectedType = AccountTypeEnum::REVENUE->value;
        }
        if ('asset' === $this->expectedType) {
            $this->expectedType = AccountTypeEnum::ASSET->value;
        }
        app('log')->debug(sprintf('Expected type is "%s"', $this->expectedType));
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return (string) trans('validation.unique_account_number_for_user');
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!auth()->check()) {
            return;
        }
        if (null === $this->expectedType) {
            return;
        }
        if (is_array($value)) {
            $fail('validation.generic_invalid')->translate();

            return;
        }
        $value     = (string) $value;
        $maxCounts = $this->getMaxOccurrences();

        foreach ($maxCounts as $type => $max) {
            $count = $this->countHits($type, $value);
            app('log')->debug(sprintf('Count for "%s" and account number "%s" is %d', $type, $value, $count));
            if ($count > $max) {
                app('log')->debug(
                    sprintf(
                        'account number "%s" is in use with %d account(s) of type "%s", which is too much for expected type "%s"',
                        $value,
                        $count,
                        $type,
                        $this->expectedType
                    )
                );

                $fail('validation.unique_account_number_for_user')->translate();

                return;
            }
        }
        app('log')->debug('Account number is valid.');
    }

    private function getMaxOccurrences(): array
    {
        $maxCounts = [
            AccountTypeEnum::ASSET->value   => 0,
            AccountTypeEnum::EXPENSE->value => 0,
            AccountTypeEnum::REVENUE->value => 0,
        ];

        if ('expense' === $this->expectedType || AccountTypeEnum::EXPENSE->value === $this->expectedType) {
            // IBAN should be unique amongst expense and asset accounts.
            // may appear once in revenue accounts
            $maxCounts[AccountTypeEnum::REVENUE->value] = 1;
        }
        if ('revenue' === $this->expectedType || AccountTypeEnum::REVENUE->value === $this->expectedType) {
            // IBAN should be unique amongst revenue and asset accounts.
            // may appear once in expense accounts
            $maxCounts[AccountTypeEnum::EXPENSE->value] = 1;
        }

        return $maxCounts;
    }

    private function countHits(string $type, string $accountNumber): int
    {
        $query = AccountMeta::leftJoin('accounts', 'accounts.id', '=', 'account_meta.account_id')
            ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
            ->where('accounts.user_id', auth()->user()->id)
            ->where('account_types.type', $type)
            ->where('account_meta.name', '=', 'account_number')
            ->where('account_meta.data', json_encode($accountNumber))
        ;

        if ($this->account instanceof Account) {
            $query->where('accounts.id', '!=', $this->account->id);
        }

        return $query->count();
    }
}
