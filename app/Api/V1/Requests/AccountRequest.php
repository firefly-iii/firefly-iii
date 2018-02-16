<?php
/**
 * AccountRequest.php
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
 * Class AccountRequest
 */
class AccountRequest extends Request
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
        $data = [
            'name'                 => $this->string('name'),
            'active'               => $this->boolean('active'),
            'accountType'          => $this->string('type'),
            'currency_id'          => $this->integer('currency_id'),
            'currency_code'        => $this->string('currency_code'),
            'virtualBalance'       => $this->string('virtual_balance'),
            'iban'                 => $this->string('iban'),
            'BIC'                  => $this->string('bic'),
            'accountNumber'        => $this->string('account_number'),
            'accountRole'          => $this->string('account_role'),
            'openingBalance'       => $this->string('opening_balance'),
            'openingBalanceDate'   => $this->date('opening_balance_date'),
            'ccType'               => $this->string('cc_type'),
            'ccMonthlyPaymentDate' => $this->string('cc_monthly_payment_date'),
            'notes'                => $this->string('notes'),
        ];

        return $data;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $accountRoles   = join(',', config('firefly.accountRoles'));
        $types          = join(',', array_keys(config('firefly.subTitlesByIdentifier')));
        $ccPaymentTypes = join(',', array_keys(config('firefly.ccTypes')));
        $rules          = [
            'name'                    => 'required|min:1|uniqueAccountForUser',
            'opening_balance'         => 'numeric|required_with:opening_balance_date|nullable',
            'opening_balance_date'    => 'date|required_with:opening_balance|nullable',
            'iban'                    => 'iban|nullable',
            'bic'                     => 'bic|nullable',
            'virtual_balance'         => 'numeric|nullable',
            'currency_id'             => 'numeric|exists:transaction_currencies,id|required_without:currency_code',
            'currency_code'           => 'min:3|max:3|exists:transaction_currencies,code|required_without:currency_id',
            'account_number'          => 'between:1,255|nullable|uniqueAccountNumberForUser',
            'account_role'            => 'in:' . $accountRoles . '|required_if:type,asset',
            'active'                  => 'required|boolean',
            'cc_type'                 => 'in:' . $ccPaymentTypes . '|required_if:account_role,ccAsset',
            'cc_monthly_payment_date' => 'date' . '|required_if:account_role,ccAsset|required_if:cc_type,monthlyFull',
            'type'                    => 'required|in:' . $types,
            'notes'                   => 'min:0|max:65536',
        ];
        switch ($this->method()) {
            default:
                break;
            case 'PUT':
            case 'PATCH':
                $account                 = $this->route()->parameter('account');
                $rules['name']           .= ':' . $account->id;
                $rules['account_number'] .= ':' . $account->id;
                break;
        }

        return $rules;
    }
}