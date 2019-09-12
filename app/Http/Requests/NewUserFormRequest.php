<?php
/**
 * NewUserFormRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Requests;

/**
 * Class NewUserFormRequest.
 *
 * @codeCoverageIgnore
 */
class NewUserFormRequest extends Request
{
    /**
     * Verify the request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * Rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        // fixed
        return [
            'bank_name'                            => 'required|between:1,200',
            'bank_balance'                         => 'required|numeric|max:1000000000',
            'savings_balance'                      => 'numeric|max:1000000000',
            'credit_card_limit'                    => 'numeric|max:1000000000',
            'amount_currency_id_bank_balance'      => 'exists:transaction_currencies,id',
            'amount_currency_id_savings_balance'   => 'exists:transaction_currencies,id',
            'amount_currency_id_credit_card_limit' => 'exists:transaction_currencies,id',
        ];
    }
}
