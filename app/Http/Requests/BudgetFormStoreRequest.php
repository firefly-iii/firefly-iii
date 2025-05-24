<?php

/**
 * BudgetFormStoreRequest.php
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

use FireflyIII\Rules\IsValidPositiveAmount;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\Validation\AutoBudget\ValidatesAutoBudgetRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Class BudgetFormStoreRequest
 */
class BudgetFormStoreRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;
    use ValidatesAutoBudgetRequest;

    /**
     * Returns the data required by the controller.
     */
    public function getBudgetData(): array
    {
        return [
            'name'               => $this->convertString('name'),
            'active'             => $this->boolean('active'),
            'auto_budget_type'   => $this->convertInteger('auto_budget_type'),
            'currency_id'        => $this->convertInteger('auto_budget_currency_id'),
            'auto_budget_amount' => $this->convertString('auto_budget_amount'),
            'auto_budget_period' => $this->convertString('auto_budget_period'),
        ];
    }

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        return [
            'name'                    => 'required|min:1|max:255|uniqueObjectForUser:budgets,name',
            'active'                  => 'numeric|min:0|max:1',
            'auto_budget_type'        => 'numeric|integer|gte:0|lte:3',
            'auto_budget_currency_id' => 'exists:transaction_currencies,id',
            'auto_budget_amount'      => ['required_if:auto_budget_type,1', 'required_if:auto_budget_type,2', new IsValidPositiveAmount()],
            'auto_budget_period'      => 'in:daily,weekly,monthly,quarterly,half_year,yearly',
            'notes'                   => 'min:1|max:32768|nullable',
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
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
