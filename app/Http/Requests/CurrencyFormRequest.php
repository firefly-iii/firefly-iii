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

/**
 * Class CurrencyFormRequest.
 */
class CurrencyFormRequest extends Request
{
    /**
     * Verify the request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * Returns the data required by the controller.
     *
     * @return array
     */
    public function getCurrencyData(): array
    {
        return [
            'name'           => $this->string('name'),
            'code'           => $this->string('code'),
            'symbol'         => $this->string('symbol'),
            'decimal_places' => $this->integer('decimal_places'),
            'enabled'        => $this->boolean('enabled'),
        ];
    }

    /**
     * Rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        // fixed
        $rules = [
            'name'           => 'required|max:48|min:1|unique:transaction_currencies,name',
            'code'           => 'required|min:3|max:3|unique:transaction_currencies,code',
            'symbol'         => 'required|min:1|max:8|unique:transaction_currencies,symbol',
            'decimal_places' => 'required|min:0|max:12|numeric',
            'enabled'        => 'in:0,1',
        ];

        /** @var TransactionCurrency $currency */
        $currency = $this->route()->parameter('currency');

        if (null !== $currency) {
            $rules = [
                'name'           => 'required|max:48|min:1',
                'code'           => 'required|min:3|max:3',
                'symbol'         => 'required|min:1|max:8',
                'decimal_places' => 'required|min:0|max:12|numeric',
                'enabled'        => 'in:0,1',
            ];
        }

        return $rules;
    }
}
