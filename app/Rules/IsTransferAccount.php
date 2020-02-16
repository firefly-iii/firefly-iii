<?php
/**
 * IsTransferAccount.php
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

namespace FireflyIII\Rules;


use FireflyIII\Models\TransactionType;
use FireflyIII\Validation\AccountValidator;
use Illuminate\Contracts\Validation\Rule;
use Log;

/**
 * Class IsTransferAccount
 */
class IsTransferAccount implements Rule
{
    /**
     * Get the validation error message.
     *
     * @return string|array
     */
    public function message(): string
    {
        return (string)trans('validation.not_transfer_account');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        Log::debug(sprintf('Now in %s(%s)', __METHOD__, $value));
        /** @var AccountValidator $validator */
        $validator = app(AccountValidator::class);
        $validator->setTransactionType(TransactionType::TRANSFER);
        $validator->setUser(auth()->user());

        $validAccount = $validator->validateSource(null, (string)$value);
        if (true === $validAccount) {
            Log::debug('Found account based on name. Return true.');

            // found by name, use repos to return.
            return true;
        }
        $validAccount = $validator->validateSource((int)$value, null);
        Log::debug(sprintf('Search by id (%d), result is %s.', (int)$value, var_export($validAccount, true)));

        return !(false === $validAccount);
    }
}