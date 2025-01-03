<?php

/**
 * IsTransferAccount.php
 * Copyright (c) 2020 james@firefly-iii.org
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

use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Models\TransactionType;
use FireflyIII\Validation\AccountValidator;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Class IsTransferAccount
 */
class IsTransferAccount implements ValidationRule
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        app('log')->debug(sprintf('Now in %s(%s)', __METHOD__, $value));

        /** @var AccountValidator $validator */
        $validator    = app(AccountValidator::class);
        $validator->setTransactionType(TransactionTypeEnum::TRANSFER->value);
        $validator->setUser(auth()->user());

        $validAccount = $validator->validateSource(['name' => (string) $value]);
        if (true === $validAccount) {
            app('log')->debug('Found account based on name. Return true.');

            // found by name, use repos to return.
            return;
        }
        $validAccount = $validator->validateSource(['id' => (int) $value]);
        app('log')->debug(sprintf('Search by id (%d), result is %s.', (int) $value, var_export($validAccount, true)));

        if (false === $validAccount) {
            $fail('validation.not_transfer_account')->translate();
        }
    }
}
