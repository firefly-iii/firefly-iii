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

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Trait CurrencyValidation
 *
 * This trait contains validation methods that have to do with currencies.
 */
trait CurrencyValidation
{
    public const string TEST = 'Test';

    /**
     * If the transactions contain foreign amounts, there must also be foreign currency information.
     */
    protected function validateForeignCurrencyInformation(Validator $validator): void
    {
        if ($validator->errors()->count() > 0) {
            return;
        }
        app('log')->debug('Now in validateForeignCurrencyInformation()');
        $transactions = $this->getTransactionsArray($validator);

        foreach ($transactions as $index => $transaction) {
            if (!is_array($transaction)) {
                continue;
            }

            if (!array_key_exists('foreign_amount', $transaction) && !array_key_exists('foreign_currency_id', $transaction) && !array_key_exists('foreign_currency_code', $transaction)) {
                Log::debug('validateForeignCurrencyInformation: no foreign currency information present at all.');

                continue;
            }
            $foreignAmount = (string) ($transaction['foreign_amount'] ?? '');
            $foreignId     = $transaction['foreign_currency_id'] ?? null;
            $foreignCode   = $transaction['foreign_currency_code'] ?? null;
            if ('' === $foreignAmount) {
                Log::debug('validateForeignCurrencyInformation: foreign amount is "".');
                if (
                    (array_key_exists('foreign_currency_id', $transaction) || array_key_exists('foreign_currency_code', $transaction))
                    && (null !== $foreignId || null !== $foreignCode)
                ) {
                    $validator->errors()->add('transactions.'.$index.'.foreign_amount', (string) trans('validation.require_currency_amount'));
                    $validator->errors()->add('transactions.'.$index.'.foreign_currency_id', (string) trans('validation.require_currency_amount'));
                    $validator->errors()->add('transactions.'.$index.'.foreign_currency_code', (string) trans('validation.require_currency_amount'));
                }

                continue;
            }

            $compare       = bccomp('0', $foreignAmount);
            if (-1 === $compare) {
                Log::debug('validateForeignCurrencyInformation: array contains foreign amount info.');
                if (!array_key_exists('foreign_currency_id', $transaction) && !array_key_exists('foreign_currency_code', $transaction)) {
                    Log::debug('validateForeignCurrencyInformation: array contains NO foreign currency info.');
                    $validator->errors()->add('transactions.'.$index.'.foreign_amount', (string) trans('validation.require_currency_info'));
                }
            }
            if (0 === $compare && ('' !== (string) $foreignId || '' !== (string) $foreignCode)) {
                Log::debug('validateForeignCurrencyInformation: array contains foreign currency info, but zero amount.');
                $validator->errors()->add('transactions.'.$index.'.foreign_currency_id', (string) trans('validation.require_currency_amount'));
                $validator->errors()->add('transactions.'.$index.'.foreign_currency_code', (string) trans('validation.require_currency_amount'));
                $validator->errors()->add('transactions.'.$index.'.foreign_amount', (string) trans('validation.require_currency_amount'));
            }
        }
    }

    abstract protected function getTransactionsArray(Validator $validator): array;
}
