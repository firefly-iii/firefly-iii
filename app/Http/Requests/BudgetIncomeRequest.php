<?php

/**
 * BudgetIncomeRequest.php
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

use Illuminate\Contracts\Validation\Validator;
use FireflyIII\Rules\IsValidPositiveAmount;
use FireflyIII\Support\Request\ChecksLogin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * Class BudgetIncomeRequest.
 */
class BudgetIncomeRequest extends FormRequest
{
    use ChecksLogin;

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        // fixed
        return [
            'amount' => ['required', new IsValidPositiveAmount()],
            'start'  => 'required|date|before:end',
            'end'    => 'required|date|after:start',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
