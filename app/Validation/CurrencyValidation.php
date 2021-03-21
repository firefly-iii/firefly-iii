<?php

/**
 * CurrencyValidation.php
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

namespace FireflyIII\Validation;


use Illuminate\Validation\Validator;
use Log;

/**
 * Trait CurrencyValidation
 *
 * This trait contains validation methods that have to do with currencies.
 */
trait CurrencyValidation
{
    /**
     * If the transactions contain foreign amounts, there must also be foreign currency information.
     *
     * @param Validator $validator
     */
    protected function validateForeignCurrencyInformation(Validator $validator): void
    {
        Log::debug('Now in validateForeignCurrencyInformation()');
        $transactions = $this->getTransactionsArray($validator);

        foreach ($transactions as $index => $transaction) {
            // if foreign amount is present, then the currency must be as well.
            if (isset($transaction['foreign_amount']) && !(isset($transaction['foreign_currency_id']) || isset($transaction['foreign_currency_code']))
                && 0 !== bccomp('0', $transaction['foreign_amount'])
            ) {
                $validator->errors()->add(
                    'transactions.' . $index . '.foreign_amount',
                    (string)trans('validation.require_currency_info')
                );
            }
            // if the currency is present, then the amount must be present as well.
            if ((isset($transaction['foreign_currency_id']) || isset($transaction['foreign_currency_code'])) && !isset($transaction['foreign_amount'])) {
                $validator->errors()->add(
                    'transactions.' . $index . '.foreign_amount',
                    (string)trans('validation.require_currency_amount')
                );
            }
        }
    }

    /**
     * @param Validator $validator
     *
     * @return array
     */
    abstract protected function getTransactionsArray(Validator $validator): array;
}
