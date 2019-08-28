<?php
/**
 * CurrencyRequest.php
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

namespace FireflyIII\Api\V1\Requests;

use FireflyIII\Rules\IsBoolean;


/**
 * Class CurrencyRequest
 * @codeCoverageIgnore
 * TODO AFTER 4.8,0: split this into two request classes.
 */
class CurrencyRequest extends Request
{
    /**
     * Authorize logged in users.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow authenticated users
        return auth()->check();
    }

    /**
     * Get all data from the request.
     *
     * @return array
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
            'name'           => $this->string('name'),
            'code'           => $this->string('code'),
            'symbol'         => $this->string('symbol'),
            'decimal_places' => $this->integer('decimal_places'),
            'default'        => $default,
            'enabled'        => $enabled,
        ];
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'name'           => 'required|between:1,255|unique:transaction_currencies,name',
            'code'           => 'required|between:3,3|unique:transaction_currencies,code',
            'symbol'         => 'required|between:1,8|unique:transaction_currencies,symbol',
            'decimal_places' => 'between:0,20|numeric|min:0|max:20',
            'enabled'        => [new IsBoolean()],
            'default'        => [new IsBoolean()],

        ];

        switch ($this->method()) {
            default:
                break;
            case 'PUT':
            case 'PATCH':
                $currency        = $this->route()->parameter('currency_code');
                $rules['name']   = 'required|between:1,255|unique:transaction_currencies,name,' . $currency->id;
                $rules['code']   = 'required|between:3,3|unique:transaction_currencies,code,' . $currency->id;
                $rules['symbol'] = 'required|between:1,8|unique:transaction_currencies,symbol,' . $currency->id;
                break;
        }

        return $rules;

    }
}
