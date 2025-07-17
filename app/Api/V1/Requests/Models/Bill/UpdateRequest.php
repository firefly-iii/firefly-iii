<?php

/**
 * BillUpdateRequest.php
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

namespace FireflyIII\Api\V1\Requests\Models\Bill;

use Illuminate\Contracts\Validation\Validator;
use FireflyIII\Models\Bill;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Rules\IsValidPositiveAmount;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

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
        $fields = [
            'name'               => ['name', 'convertString'],
            'amount_min'         => ['amount_min', 'convertString'],
            'amount_max'         => ['amount_max', 'convertString'],
            'currency_id'        => ['currency_id', 'convertInteger'],
            'currency_code'      => ['currency_code', 'convertString'],
            'date'               => ['date', 'date'],
            'end_date'           => ['end_date', 'date'],
            'extension_date'     => ['extension_date', 'date'],
            'repeat_freq'        => ['repeat_freq', 'convertString'],
            'skip'               => ['skip', 'convertInteger'],
            'active'             => ['active', 'boolean'],
            'order'              => ['order', 'convertInteger'],
            'notes'              => ['notes', 'stringWithNewlines'],
            'object_group_id'    => ['object_group_id', 'convertInteger'],
            'object_group_title' => ['object_group_title', 'convertString'],
        ];

        return $this->getAllData($fields);
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        /** @var Bill $bill */
        $bill = $this->route()->parameter('bill');

        return [
            'name'           => sprintf('min:1|max:255|uniqueObjectForUser:bills,name,%d', $bill->id),
            'amount_min'     => ['nullable', new IsValidPositiveAmount()],
            'amount_max'     => ['nullable', new IsValidPositiveAmount()],
            'currency_id'    => 'numeric|exists:transaction_currencies,id',
            'currency_code'  => 'min:3|max:51|exists:transaction_currencies,code',
            'date'           => 'date|after:1970-01-02|before:2038-01-17',
            'end_date'       => 'date|after:date|after:1970-01-02|before:2038-01-17',
            'extension_date' => 'date|after:date|after:1970-01-02|before:2038-01-17',
            'repeat_freq'    => 'in:weekly,monthly,quarterly,half-year,yearly',
            'skip'           => 'min:0|max:31|numeric',
            'active'         => [new IsBoolean()],
            'notes'          => 'min:1|max:32768',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            static function (Validator $validator): void {
                $data = $validator->getData();
                if (array_key_exists('amount_min', $data) && array_key_exists('amount_max', $data)) {
                    $min = $data['amount_min'] ?? '0';
                    $max = $data['amount_max'] ?? '0';

                    if (1 === bccomp($min, $max)) {
                        $validator->errors()->add('amount_min', (string) trans('validation.amount_min_over_max'));
                    }
                }
            }
        );
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
