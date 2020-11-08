<?php
/**
 * BudgetLimitUpdateRequest.php
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

namespace FireflyIII\Api\V1\Requests;

use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class BudgetLimitUpdateRequest
 *
 * @codeCoverageIgnore
 */
class BudgetLimitUpdateRequest extends FormRequest
{
    use ConvertsDataTypes, ChecksLogin;

    /**
     * Get all data from the request.
     *
     * @return array
     */
    public function getAll(): array
    {
        $data = [
            'budget_id'     => $this->integer('budget_id'),
            'start'         => $this->date('start'),
            'end'           => $this->date('end'),
            'amount'        => $this->string('amount'),
            'currency_id'   => $this->integer('currency_id'),
            'currency_code' => $this->string('currency_code'),
        ];
        // if request has a budget already, drop the rule.
        $budget = $this->route()->parameter('budget');
        if (null !== $budget) {
            $data['budget_id'] = $budget->id;
        }
        return $data;
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'budget_id'     => 'required|exists:budgets,id|belongsToUser:budgets,id',
            'start'         => 'required|before:end|date',
            'end'           => 'required|after:start|date',
            'amount'        => 'required|gt:0',
            'currency_id'   => 'numeric|exists:transaction_currencies,id',
            'currency_code' => 'min:3|max:3|exists:transaction_currencies,code',
        ];
        // if request has a budget already, drop the rule.
        $budget = $this->route()->parameter('budget');
        if (null !== $budget) {
            unset($rules['budget_id']);
        }

        return $rules;
    }

}
