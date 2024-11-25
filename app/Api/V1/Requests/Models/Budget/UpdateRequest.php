<?php

/**
 * BudgetUpdateRequest.php
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

namespace FireflyIII\Api\V1\Requests\Models\Budget;

use FireflyIII\Models\Budget;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Rules\IsValidPositiveAmount;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\Validation\AutoBudget\ValidatesAutoBudgetRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Class UpdateRequest
 */
class UpdateRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;
    use ValidatesAutoBudgetRequest;

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        // this is the way:
        $fields  = [
            'name'               => ['name', 'convertString'],
            'active'             => ['active', 'boolean'],
            'order'              => ['order', 'convertInteger'],
            'notes'              => ['notes', 'convertString'],
            'currency_id'        => ['auto_budget_currency_id', 'convertInteger'],
            'currency_code'      => ['auto_budget_currency_code', 'convertString'],
            'auto_budget_type'   => ['auto_budget_type', 'convertString'],
            'auto_budget_amount' => ['auto_budget_amount', 'convertString'],
            'auto_budget_period' => ['auto_budget_period', 'convertString'],
        ];
        $allData = $this->getAllData($fields);
        if (array_key_exists('auto_budget_type', $allData)) {
            $types                       = [
                'none'     => 0,
                'reset'    => 1,
                'rollover' => 2,
                'adjusted' => 3,
            ];
            $allData['auto_budget_type'] = $types[$allData['auto_budget_type']] ?? 0;
        }

        return $allData;
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        /** @var Budget $budget */
        $budget = $this->route()->parameter('budget');

        return [
            'name'                      => sprintf('min:1|max:100|uniqueObjectForUser:budgets,name,%d', $budget->id),
            'active'                    => [new IsBoolean()],
            'notes'                     => 'nullable|min:1|max:32768',
            'auto_budget_type'          => 'in:reset,rollover,adjusted,none',
            'auto_budget_currency_id'   => 'exists:transaction_currencies,id',
            'auto_budget_currency_code' => 'exists:transaction_currencies,code',
            'auto_budget_amount'        => ['nullable', new IsValidPositiveAmount()],
            'auto_budget_period'        => 'in:daily,weekly,monthly,quarterly,half_year,yearly',
        ];
    }

    /**
     * Configure the validator instance with special rules for after the basic validation rules.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                // validate all account info
                $this->validateAutoBudgetAmount($validator);
            }
        );
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', __CLASS__), $validator->errors()->toArray());
        }
    }
}
