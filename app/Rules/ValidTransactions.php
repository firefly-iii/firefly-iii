<?php

/**
 * ValidTransactions.php
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

use FireflyIII\Models\Transaction;
use Illuminate\Contracts\Validation\Rule;
use Log;

/**
 * Class ValidTransactions
 */
class ValidTransactions implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.invalid_selection');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        Log::debug('In ValidTransactions::passes');
        if (!\is_array($value)) {
            return true;
        }
        $userId = auth()->user()->id;
        foreach ($value as $transactionId) {
            $count = Transaction::where('transactions.id', $transactionId)
                                ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                ->where('accounts.user_id', $userId)->count();
            if ($count === 0) {
                Log::debug(sprintf('Count for transaction #%d and user #%d is zero! Return FALSE', $transactionId, $userId));

                return false;
            }
        }
        Log::debug('Return true!');

        return true;
    }
}
