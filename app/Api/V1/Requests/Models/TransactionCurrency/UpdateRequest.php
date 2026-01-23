<?php

/**
 * CurrencyUpdateRequest.php
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

namespace FireflyIII\Api\V1\Requests\Models\TransactionCurrency;

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateRequest
 */
class UpdateRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        // return nothing that isn't explicitly in the array:
        $fields = [
            'name'           => ['name', 'convertString'],
            'code'           => ['code', 'convertString'],
            'symbol'         => ['symbol', 'convertString'],
            'decimal_places' => ['decimal_places', 'convertInteger'],
            'default'        => ['default', 'boolean'],
            'enabled'        => ['enabled', 'boolean'],
        ];

        return $this->getAllData($fields);
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        /** @var TransactionCurrency $currency */
        $currency = $this->route()->parameter('currency_code');

        return [
            'name'           => sprintf('min:1|max:255|unique:transaction_currencies,name,%d', $currency->id),
            'code'           => sprintf('min:3|max:32|unique:transaction_currencies,code,%d', $currency->id),
            'symbol'         => sprintf('min:1|max:32|unique:transaction_currencies,symbol,%d', $currency->id),
            'decimal_places' => 'numeric|min:0|max:12',
            'enabled'        => [new IsBoolean()],
            'default'        => [new IsBoolean()],
        ];
    }
}
