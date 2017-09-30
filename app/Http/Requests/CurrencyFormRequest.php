<?php
/**
 * CurrencyFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
