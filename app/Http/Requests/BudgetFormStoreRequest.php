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

use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\Validation\AutoBudget\ValidatesAutoBudgetRequest;
use Illuminate\Validation\Validator;

/**
 * @codeCoverageIgnore
 * Class BudgetFormStoreRequest
 */
class BudgetFormStoreRequest extends LoggedInRequest
{
    use ConvertsDataTypes, ValidatesAutoBudgetRequest;

    /**
     * Returns the data required by the controller.
     *
     * @return array
     */
    public function getBudgetData(): array
    {
        return [
            'name'                    => $this->string('name'),
            'active'                  => $this->boolean('active'),
            'auto_budget_type'        => $this->integer('auto_budget_type'),
            'transaction_currency_id' => $this->integer('transaction_currency_id'),
            'auto_budget_amount'      => $this->string('auto_budget_amount'),
            'auto_budget_period'      => $this->string('auto_budget_period'),
        ];
    }

    /**
     * Rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name'                    => 'required|between:1,100|uniqueObjectForUser:budgets,name',
            'active'                  => 'numeric|between:0,1',
            'auto_budget_type'        => 'numeric|between:0,2',
            'auto_budget_currency_id' => 'exists:transaction_currencies,id',
            'auto_budget_amount'      => 'min:0|max:1000000000',
            'auto_budget_period'      => 'in:daily,weekly,monthly,quarterly,half_year,yearly',
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
                // validate all account info
                $this->validateAutoBudgetAmount($validator);
            }
        );
    }

}
