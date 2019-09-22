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

use FireflyIII\Rules\IsBoolean;
use Illuminate\Validation\Validator;

/**
 * Class BillRequest
 *
 * TODO AFTER 4.8,0: split this into two request classes.
 *
 * @codeCoverageIgnore
 */
class BillRequest extends Request
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
        $active = true;
        if (null !== $this->get('active')) {
            $active = $this->boolean('active');
        }

        $data = [
            'name'          => $this->string('name'),
            'amount_min'    => $this->string('amount_min'),
            'amount_max'    => $this->string('amount_max'),
            'currency_id'   => $this->integer('currency_id'),
            'currency_code' => $this->string('currency_code'),
            'date'          => $this->date('date'),
            'repeat_freq'   => $this->string('repeat_freq'),
            'skip'          => $this->integer('skip'),
            'active'        => $active,
            'notes'         => $this->nlString('notes'),
        ];

        return $data;
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     *
     */
    public function rules(): array
    {
        $rules = [
            'name'          => 'required|between:1,255|uniqueObjectForUser:bills,name',
            'amount_min'    => 'required|numeric|more:0',
            'amount_max'    => 'required|numeric|more:0',
            'currency_id'   => 'numeric|exists:transaction_currencies,id',
            'currency_code' => 'min:3|max:3|exists:transaction_currencies,code',
            'date'          => 'required|date',
            'repeat_freq'   => 'required|in:weekly,monthly,quarterly,half-year,yearly',
            'skip'          => 'between:0,31',
            'active'        => [new IsBoolean],
            'notes'         => 'between:1,65536',
        ];
        switch ($this->method()) {
            default:
                break;
            case 'PUT':
            case 'PATCH':
                $bill          = $this->route()->parameter('bill');
                $rules['name'] .= ',' . $bill->id;
                break;
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            static function (Validator $validator) {
                $data = $validator->getData();
                $min  = (float)($data['amount_min'] ?? 0);
                $max  = (float)($data['amount_max'] ?? 0);
                if ($min > $max) {
                    $validator->errors()->add('amount_min', (string)trans('validation.amount_min_over_max'));
                }
            }
        );
    }
}
