<?php

/**
 * AccountFilter.php
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

namespace FireflyIII\Support\Http\Api;

use FireflyIII\Enums\AccountTypeEnum;

/**
 * Trait AccountFilter
 */
trait AccountFilter
{
    protected array $types
        = [
            'all'                                   => [
                AccountTypeEnum::DEFAULT->value,
                AccountTypeEnum::CASH->value,
                AccountTypeEnum::ASSET->value,
                AccountTypeEnum::EXPENSE->value,
                AccountTypeEnum::REVENUE->value,
                AccountTypeEnum::INITIAL_BALANCE->value,
                AccountTypeEnum::BENEFICIARY->value,
                AccountTypeEnum::IMPORT->value,
                AccountTypeEnum::RECONCILIATION->value,
                AccountTypeEnum::LOAN->value,
                AccountTypeEnum::DEBT->value,
                AccountTypeEnum::MORTGAGE->value,
            ],
            'asset'                                 => [AccountTypeEnum::DEFAULT->value, AccountTypeEnum::ASSET->value],
            'cash'                                  => [AccountTypeEnum::CASH->value],
            'expense'                               => [AccountTypeEnum::EXPENSE->value, AccountTypeEnum::BENEFICIARY->value],
            'revenue'                               => [AccountTypeEnum::REVENUE->value],
            'special'                               => [AccountTypeEnum::CASH->value, AccountTypeEnum::INITIAL_BALANCE->value, AccountTypeEnum::IMPORT->value, AccountTypeEnum::RECONCILIATION->value],
            'hidden'                                => [AccountTypeEnum::INITIAL_BALANCE->value, AccountTypeEnum::IMPORT->value, AccountTypeEnum::RECONCILIATION->value],
            'liability'                             => [AccountTypeEnum::DEBT->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::CREDITCARD->value],
            'liabilities'                           => [AccountTypeEnum::DEBT->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::CREDITCARD->value],
            AccountTypeEnum::DEFAULT->value         => [AccountTypeEnum::DEFAULT->value],
            AccountTypeEnum::CASH->value            => [AccountTypeEnum::CASH->value],
            AccountTypeEnum::ASSET->value           => [AccountTypeEnum::ASSET->value],
            AccountTypeEnum::EXPENSE->value         => [AccountTypeEnum::EXPENSE->value],
            AccountTypeEnum::REVENUE->value         => [AccountTypeEnum::REVENUE->value],
            AccountTypeEnum::INITIAL_BALANCE->value => [AccountTypeEnum::INITIAL_BALANCE->value],
            AccountTypeEnum::BENEFICIARY->value     => [AccountTypeEnum::BENEFICIARY->value],
            AccountTypeEnum::IMPORT->value          => [AccountTypeEnum::IMPORT->value],
            AccountTypeEnum::RECONCILIATION->value  => [AccountTypeEnum::RECONCILIATION->value],
            AccountTypeEnum::LOAN->value            => [AccountTypeEnum::LOAN->value],
            AccountTypeEnum::MORTGAGE->value        => [AccountTypeEnum::MORTGAGE->value],
            AccountTypeEnum::DEBT->value            => [AccountTypeEnum::DEBT->value],
            AccountTypeEnum::CREDITCARD->value      => [AccountTypeEnum::CREDITCARD->value],
            'default account'                       => [AccountTypeEnum::DEFAULT->value],
            'cash account'                          => [AccountTypeEnum::CASH->value],
            'asset account'                         => [AccountTypeEnum::ASSET->value],
            'expense account'                       => [AccountTypeEnum::EXPENSE->value],
            'revenue account'                       => [AccountTypeEnum::REVENUE->value],
            'initial balance account'               => [AccountTypeEnum::INITIAL_BALANCE->value],
            'reconciliation'                        => [AccountTypeEnum::RECONCILIATION->value],
            'loan'                                  => [AccountTypeEnum::LOAN->value],
            'mortgage'                              => [AccountTypeEnum::MORTGAGE->value],
            'debt'                                  => [AccountTypeEnum::DEBT->value],
            'credit card'                           => [AccountTypeEnum::CREDITCARD->value],
            'credit-card'                           => [AccountTypeEnum::CREDITCARD->value],
            'creditcard'                            => [AccountTypeEnum::CREDITCARD->value],
            'cc'                                    => [AccountTypeEnum::CREDITCARD->value],
        ];

    /**
     * All the available types.
     */
    protected function mapAccountTypes(string $type): array
    {
        return $this->types[$type] ?? $this->types['all'];
    }
}
