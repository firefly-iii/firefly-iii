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


/**
 * Class CurrencyRequest
 */
class CurrencyRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow authenticated users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return [
            'name'           => $this->string('name'),
            'code'           => $this->string('code'),
            'symbol'         => $this->string('symbol'),
            'decimal_places' => $this->integer('decimal_places'),
            'default'        => $this->boolean('default'),
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'name'           => 'required|between:1,255|unique:transaction_currencies,name',
            'code'           => 'required|between:3,3|unique:transaction_currencies,code',
            'symbol'         => 'required|between:1,5|unique:transaction_currencies,symbol',
            'decimal_places' => 'required|between:0,20|numeric|min:0|max:20',
            'default'        => 'in:true,false',
        ];

        switch ($this->method()) {
            default:
                break;
            case 'PUT':
            case 'PATCH':
                $currency        = $this->route()->parameter('currency');
                $rules['name']   = 'required|between:1,255|unique:transaction_currencies,name,' . $currency->id;
                $rules['code']   = 'required|between:1,255|unique:transaction_currencies,code,' . $currency->id;
                $rules['symbol'] = 'required|between:1,255|unique:transaction_currencies,symbol,' . $currency->id;
                break;
        }

        return $rules;

    }
}
