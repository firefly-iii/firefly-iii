<?php

/**
 * AccountStoreRequest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

use FireflyIII\Models\Location;
use FireflyIII\Rules\IsBoolean;

/**
 * Class AccountStoreRequest
 *
 * @codeCoverageIgnore
 */
class AccountStoreRequest extends Request
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
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        $accountRoles   = implode(',', config('firefly.accountRoles'));
        $types          = implode(',', array_keys(config('firefly.subTitlesByIdentifier')));
        $ccPaymentTypes = implode(',', array_keys(config('firefly.ccTypes')));
        $rules          = [
            'name'                 => 'required|min:1|uniqueAccountForUser',
            'type'                 => 'required|' . sprintf('in:%s', $types),
            'iban'                 => 'iban|nullable',
            'bic'                  => 'bic|nullable',
            'account_number'       => 'between:1,255|nullable|uniqueAccountNumberForUser',
            'opening_balance'      => 'numeric|required_with:opening_balance_date|nullable',
            'opening_balance_date' => 'date|required_with:opening_balance|nullable',
            'virtual_balance'      => 'numeric|nullable',
            'currency_id'          => 'numeric|exists:transaction_currencies,id',
            'currency_code'        => 'min:3|max:3|exists:transaction_currencies,code',
            'active'               => [new IsBoolean],
            'include_net_worth'    => [new IsBoolean],
            'account_role'         => sprintf('in:%s|required_if:type,asset', $accountRoles),
            'credit_card_type'     => sprintf('in:%s|required_if:account_role,ccAsset', $ccPaymentTypes),
            'monthly_payment_date' => 'date' . '|required_if:account_role,ccAsset|required_if:credit_card_type,monthlyFull',
            'liability_type'       => 'required_if:type,liability|in:loan,debt,mortgage',
            'liability_amount'     => 'required_if:type,liability|min:0|numeric',
            'liability_start_date' => 'required_if:type,liability|date',
            'interest'             => 'required_if:type,liability|between:0,100|numeric',
            'interest_period'      => 'required_if:type,liability|in:daily,monthly,yearly',
            'notes'                => 'min:0|max:65536',
        ];
        $rules = Location::requestRules($rules);

        return $rules;
    }
}
