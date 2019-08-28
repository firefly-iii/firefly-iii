<?php
/**
 * BudgetRequest.php
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

use FireflyIII\Models\Budget;
use FireflyIII\Rules\IsBoolean;

/**
 * Class BudgetRequest
 * @codeCoverageIgnore
 * TODO AFTER 4.8,0: split this into two request classes.
 */
class BudgetRequest extends Request
{
    /**
     * Authorize logged in users.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow authenticated users
        return auth()->check();
    }

    /**
     * Get all data from the request.
     *
     * @return array
     */
    public function getAll(): array
    {
        $active = true;
        if (null !== $this->get('active')) {
            $active = $this->boolean('active');
        }

        return [
            'name'   => $this->string('name'),
            'active' => $active,
            'order'  => 0,
        ];
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'name'   => 'required|between:1,100|uniqueObjectForUser:budgets,name',
            'active' => [new IsBoolean],
        ];
        switch ($this->method()) {
            default:
                break;
            case 'PUT':
            case 'PATCH':
                /** @var Budget $budget */
                $budget        = $this->route()->parameter('budget');
                $rules['name'] = sprintf('required|between:1,100|uniqueObjectForUser:budgets,name,%d', $budget->id);
                break;
        }

        return $rules;
    }
}
