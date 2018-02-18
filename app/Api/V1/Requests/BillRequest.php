<?php
/**
 * BillRequest.php
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

use Illuminate\Validation\Validator;

/**
 * Class BillRequest
 */
class BillRequest extends Request
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
    public function getAll(): array
    {
        $data = [
            'name'        => $this->string('name'),
            'match'       => $this->string('match'),
            'amount_min'  => $this->string('amount_min'),
            'amount_max'  => $this->string('amount_max'),
            //'currency_id'   => $this->integer('currency_id'),
            //'currency_code' => $this->string('currency_code'),
            'date'        => $this->date('date'),
            'repeat_freq' => $this->string('repeat_freq'),
            'skip'        => $this->integer('skip'),
            'automatch'   => $this->boolean('automatch'),
            'active'      => $this->boolean('active'),
            'notes'       => $this->string('notes'),
        ];

        return $data;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'name'        => 'required|between:1,255|uniqueObjectForUser:bills,name',
            'match'       => 'required|between:1,255|uniqueObjectForUser:bills,match',
            'amount_min'  => 'required|numeric|more:0',
            'amount_max'  => 'required|numeric|more:0',
            //'currency_id'   => 'numeric|exists:transaction_currencies,id|required_without:currency_code',
            //'currency_code' => 'min:3|max:3|exists:transaction_currencies,code|required_without:currency_id',
            'date'        => 'required|date',
            'repeat_freq' => 'required|in:weekly,monthly,quarterly,half-year,yearly',
            'skip'        => 'required|between:0,31',
            'automatch'   => 'required|boolean',
            'active'      => 'required|boolean',
            'notes'       => 'between:1,65536',
        ];
        switch ($this->method()) {
            default:
                break;
            case 'PUT':
            case 'PATCH':
                $bill           = $this->route()->parameter('bill');
                $rules['name']  .= ',' . $bill->id;
                $rules['match'] .= ',' . $bill->id;
                break;
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator) {
                $data = $validator->getData();
                $min  = floatval($data['amount_min']);
                $max  = floatval($data['amount_max']);
                if ($min > $max) {
                    $validator->errors()->add('amount_min', trans('validation.amount_min_over_max'));
                }
            }
        );
    }
}