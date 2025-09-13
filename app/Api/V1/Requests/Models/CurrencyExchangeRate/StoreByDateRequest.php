<?php

/*
 * StoreRequest.php
 * Copyright (c) 2025 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V1\Requests\Models\CurrencyExchangeRate;

use Illuminate\Validation\Validator;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

class StoreByDateRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return [
            'from'  => $this->get('from'),
            'rates' => $this->get('rates', []),
        ];
    }

    public function getFromCurrency(): TransactionCurrency
    {
        return Amount::getTransactionCurrencyByCode((string)$this->get('from'));
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'from'    => 'required|exists:transaction_currencies,code',
            'rates'   => 'required|array',
            'rates.*' => 'required|numeric|min:0.0000000001',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $from = $this->getFromCurrency();

        $validator->after(
            static function (Validator $validator) use ($from): void {
                $data  = $validator->getData();
                $rates = $data['rates'] ?? [];
                if (0 === count($rates)) {
                    $validator->errors()->add('rates', 'No rates given.');

                    return;
                }
                foreach ($rates as $key => $entry) {
                    if ($key === $from->code) {
                        $validator->errors()->add(sprintf('rates.%s', $key), trans('validation.convert_to_itself', ['code' => $key]));

                        continue;
                    }

                    try {
                        Amount::getTransactionCurrencyByCode((string)$key);
                    } catch (FireflyException) {
                        $validator->errors()->add(sprintf('rates.%s', $key), trans('validation.invalid_currency_code', ['code' => $key]));
                    }
                }
            }
        );
    }
}
