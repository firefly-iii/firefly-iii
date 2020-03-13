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

use FireflyIII\AutoBudget;
use Illuminate\Validation\Validator;

/**
 * @codeCoverageIgnore
 * Class BudgetFormStoreRequest
 */
class BudgetFormStoreRequest extends Request
{
    /**
     * Verify the request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

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
            'auto_budget_option'      => $this->integer('auto_budget_option'),
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
            'auto_budget_option'      => 'numeric|between:0,2',
            'transaction_currency_id' => 'required|exists:transaction_currencies,id',
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
                $this->validateAmount($validator);
            }
        );
    }

    /**
     * @param Validator $validator
     */
    private function validateAmount(Validator $validator): void
    {
        $data   = $validator->getData();
        $option = (int)$data['auto_budget_option'];
        $amount = $data['auto_budget_amount'] ?? '';
        switch ($option) {
            case AutoBudget::AUTO_BUDGET_RESET:
            case AutoBudget::AUTO_BUDGET_ROLLOVER:
                // basic float check:
                if ('' === $amount) {
                    $validator->errors()->add('auto_budget_amount', (string)trans('validation.amount_required_for_auto_budget'));
                }
                if (1 !== bccomp((string)$amount, '0')) {
                    $validator->errors()->add('auto_budget_amount', (string)trans('validation.auto_budget_amount_positive'));
                }
                break;
        }
    }
}
