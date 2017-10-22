<?php
/**
 * CurrencyFormRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

/**
 * Class BillFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class CurrencyFormRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getCurrencyData()
    {
        return [
            'name'           => $this->string('name'),
            'code'           => $this->string('code'),
            'symbol'         => $this->string('symbol'),
            'decimal_places' => $this->integer('decimal_places'),
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        // fixed
        $rules = [
            'name'           => 'required|max:48|min:1|unique:transaction_currencies,name',
            'code'           => 'required|min:3|max:3|unique:transaction_currencies,code',
            'symbol'         => 'required|min:1|max:8|unique:transaction_currencies,symbol',
            'decimal_places' => 'required|min:0|max:12|numeric',
        ];
        if (intval($this->get('id')) > 0) {
            $rules = [
                'name'           => 'required|max:48|min:1',
                'code'           => 'required|min:3|max:3',
                'symbol'         => 'required|min:1|max:8',
                'decimal_places' => 'required|min:0|max:12|numeric',
            ];
        }

        return $rules;
    }
}
