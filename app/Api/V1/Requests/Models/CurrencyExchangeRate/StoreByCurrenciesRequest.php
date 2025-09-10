<?php

/*
 * StoreRequest.php
 * Copyright (c) 2025 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V1\Requests\Models\CurrencyExchangeRate;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreByCurrenciesRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    public function getAll(): array
    {
        return $this->all();
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        return [
            '*' => 'required|numeric|min:0.0000000001',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(
            static function (Validator $validator): void {
                $data = $validator->getData();
                foreach ($data as $date => $rate) {
                    try {
                        Carbon::createFromFormat('Y-m-d', $date);
                    } catch (InvalidFormatException) {
                        $validator->errors()->add('date', trans('validation.date', ['attribute' => 'date']));

                        return;
                    }
                    if (!is_numeric($rate)) {
                        $validator->errors()->add('rate', trans('validation.number', ['attribute' => 'rate']));

                        return;
                    }
                }
            }
        );
    }
}
