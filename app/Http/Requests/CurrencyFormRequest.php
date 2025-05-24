<?php

/**
 * CurrencyFormRequest.php
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

namespace FireflyIII\Http\Requests;

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Class CurrencyFormRequest.
 */
class CurrencyFormRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Returns the data required by the controller.
     */
    public function getCurrencyData(): array
    {
        return [
            'name'           => $this->convertString('name'),
            'code'           => $this->convertString('code'),
            'symbol'         => $this->convertString('symbol'),
            'decimal_places' => $this->convertInteger('decimal_places'),
            'enabled'        => $this->boolean('enabled'),
        ];
    }

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        // fixed
        $rules    = [
            'name'           => 'required|max:48|min:1|uniqueCurrencyName',
            'code'           => 'required|min:3|max:51|uniqueCurrencyCode',
            'symbol'         => 'required|min:1|max:51|uniqueCurrencySymbol',
            'decimal_places' => 'required|min:0|max:12|numeric',
            'enabled'        => 'in:0,1',
        ];

        /** @var null|TransactionCurrency $currency */
        $currency = $this->route()->parameter('currency');

        if (null !== $currency) {
            return [
                'name'           => 'required|max:48|min:1',
                'code'           => 'required|min:3|max:51',
                'symbol'         => 'required|min:1|max:51',
                'decimal_places' => 'required|min:0|max:12|numeric',
                'enabled'        => 'in:0,1',
            ];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
