<?php
/**
 * BudgetLimitRequest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V1\Requests;


/**
 * Class BudgetLimitRequest
 */
class BudgetLimitRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow authenticated users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return [
            'budget_id'  => $this->integer('budget_id'),
            'start_date' => $this->date('start_date'),
            'end_date'   => $this->date('end_date'),
            'amount'     => $this->string('amount'),
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'budget_id'  => 'required|exists:budgets,id|belongsToUser:budgets,id',
            'start_date' => 'required|before:end_date|date',
            'end_date'   => 'required|after:start_date|date',
            'amount'     => 'required|more:0',
        ];
        switch ($this->method()) {
            default:
                break;
            case 'PUT':
            case 'PATCH':
                $rules['budget_id'] = 'required|exists:budgets,id|belongsToUser:budgets,id';
                break;
        }

        return $rules;
    }

}