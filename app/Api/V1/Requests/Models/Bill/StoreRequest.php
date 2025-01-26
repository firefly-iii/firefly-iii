<?php

/**
 * BillStoreRequest.php
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

use FireflyIII\Rules\IsBoolean;
use FireflyIII\Rules\IsValidPositiveAmount;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;
use TypeError;
use ValueError;

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
        app('log')->debug('Raw fields in Bill StoreRequest', $this->all());
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
        return [
            'name'           => 'min:1|max:255|uniqueObjectForUser:bills,name',
            'amount_min'     => ['required', new IsValidPositiveAmount()],
            'amount_max'     => ['required', new IsValidPositiveAmount()],
            'currency_id'    => 'numeric|exists:transaction_currencies,id',
            'currency_code'  => 'min:3|max:51|exists:transaction_currencies,code',
            'date'           => 'date|required|after:1900-01-01|before:2099-12-31',
            'end_date'       => 'nullable|date|after:date|after:1900-01-01|before:2099-12-31',
            'extension_date' => 'nullable|date|after:date|after:1900-01-01|before:2099-12-31',
            'repeat_freq'    => 'in:weekly,monthly,quarterly,half-year,yearly|required',
            'skip'           => 'min:0|max:31|numeric',
            'active'         => [new IsBoolean()],
            'notes'          => 'nullable|min:1|max:32768',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            static function (Validator $validator): void {
                $data   = $validator->getData();
                $min    = $data['amount_min'] ?? '0';
                $max    = $data['amount_max'] ?? '0';

                if(is_array($min) || is_array($max)) {
                    $validator->errors()->add('amount_min', (string) trans('validation.generic_invalid'));
                    $validator->errors()->add('amount_max', (string) trans('validation.generic_invalid'));
                    $min ='0';
                    $max = '0';
                }
                $result = false;
                try {
                    $result = bccomp($min, $max);
                } catch (ValueError $e) {
                    Log::error($e->getMessage());
                    $validator->errors()->add('amount_min', (string) trans('validation.generic_invalid'));
                    $validator->errors()->add('amount_max', (string) trans('validation.generic_invalid'));
                }

                if (1 === $result) {
                    $validator->errors()->add('amount_min', (string) trans('validation.amount_min_over_max'));
                }
            }
        );
        $failed = false;
        try {
            $failed = $validator->fails();
        } catch (TypeError $e) {
            Log::error($e->getMessage());
            $failed = false;
        }
        if ($failed) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', __CLASS__), $validator->errors()->toArray());
        }
    }
}
