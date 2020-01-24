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

namespace FireflyIII\Api\V1\Requests;

/**
 * Class AvailableBudgetRequest
 *
 * @codeCoverageIgnore
 */
class AvailableBudgetRequest extends Request
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
        return [
            'currency_id'   => $this->integer('currency_id'),
            'currency_code' => $this->string('currency_code'),
            'amount'        => $this->string('amount'),
            'start'         => $this->date('start'),
            'end'           => $this->date('end'),
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
            'currency_id'   => 'numeric|exists:transaction_currencies,id',
            'currency_code' => 'min:3|max:3|exists:transaction_currencies,code',
            'amount'        => 'required|numeric|more:0',
            'start'         => 'required|date|before:end',
            'end'           => 'required|date|after:start',
        ];

        return $rules;
    }


}
