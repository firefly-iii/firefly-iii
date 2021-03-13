<?php
/**
 * AvailableBudgetRequest.php
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

namespace FireflyIII\Api\V1\Requests\Models\AvailableBudget;

use Carbon\Carbon;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Class Request
 *
 * @codeCoverageIgnore
 */
class Request extends FormRequest
{
    use ConvertsDataTypes, ChecksLogin;

    /**
     * Get all data from the request.
     *
     * @return array
     */
    public function getAll(): array
    {
        // this is the way:
        $fields = [
            'currency_id'   => ['currency_id', 'integer'],
            'currency_code' => ['currency_code', 'string'],
            'amount'        => ['amount', 'string'],
            'start'         => ['start', 'date'],
            'end'           => ['end', 'date'],
        ];

        return $this->getAllData($fields);
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'currency_id'   => 'numeric|exists:transaction_currencies,id',
            'currency_code' => 'min:3|max:3|exists:transaction_currencies,code',
            'amount'        => 'numeric|gt:0',
            'start'         => 'date',
            'end'           => 'date',
        ];
    }

    /**
     * Configure the validator instance with special rules for after the basic validation rules.
     *
     * @param Validator $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator) {
                // validate start before end only if both are there.
                $data = $validator->getData();
                if (array_key_exists('start', $data) && array_key_exists('end', $data)) {
                    $start = new Carbon($data['start']);
                    $end   = new Carbon($data['end']);
                    if ($end->isBefore($start)) {
                        $validator->errors()->add('end', (string)trans('validation.date_after'));
                    }
                }
            }
        );
    }


}
