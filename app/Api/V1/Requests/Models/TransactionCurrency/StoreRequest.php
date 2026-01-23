<?php

/**
 * CurrencyStoreRequest.php
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

use FireflyIII\Rules\IsBoolean;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreRequest
 */
class StoreRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        $enabled = true;
        $default = false;
        if (null !== $this->get('enabled')) {
            $enabled = $this->boolean('enabled');
        }
        if (null !== $this->get('default')) {
            $default = $this->boolean('default');
        }

        return [
            'name'           => $this->convertString('name'),
            'code'           => $this->convertString('code'),
            'symbol'         => $this->convertString('symbol'),
            'decimal_places' => $this->convertInteger('decimal_places'),
            'default'        => $default,
            'enabled'        => $enabled,
        ];
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        return [
            'name'           => 'required|min:1|max:255|unique:transaction_currencies,name',
            'code'           => 'required|min:3|max:32|unique:transaction_currencies,code',
            'symbol'         => 'required|min:1|max:32|unique:transaction_currencies,symbol',
            'decimal_places' => 'numeric|min:0|max:12',
            'enabled'        => [new IsBoolean()],
            'default'        => [new IsBoolean()],
        ];
    }
}
