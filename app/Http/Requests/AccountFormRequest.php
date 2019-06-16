<?php
/**
 * AccountFormRequest.php
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

use FireflyIII\Models\Account;
use FireflyIII\Rules\UniqueIban;
use FireflyIII\Rules\ZeroOrMore;

/**
 * Class AccountFormRequest.
 */
class AccountFormRequest extends Request
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
     * Get all data.
     *
     * @return array
     */
    public function getAccountData(): array
    {
        $data = [
            'name'                    => $this->string('name'),
            'active'                  => $this->boolean('active'),
            'account_type'            => $this->string('what'),
            'account_type_id'         => 0,
            'currency_id'             => $this->integer('currency_id'),
            'virtual_balance'         => $this->string('virtualBalance'),
            'iban'                    => $this->string('iban'),
            'BIC'                     => $this->string('BIC'),
            'account_number'          => $this->string('accountNumber'),
            'account_role'            => $this->string('accountRole'),
            'opening_balance'         => $this->string('openingBalance'),
            'opening_balance_date'    => $this->date('openingBalanceDate'),
            'cc_type'                 => $this->string('ccType'),
            'cc_monthly_payment_date' => $this->string('ccMonthlyPaymentDate'),
            'notes'                   => $this->string('notes'),
            'interest'                => $this->string('interest'),
            'interest_period'         => $this->string('interest_period'),
            'include_net_worth'       => '1',
        ];
        if (false === $this->boolean('include_net_worth')) {
            $data['include_net_worth'] = '0';
        }

        // if the account type is "liabilities" there are actually four types of liability
        // that could have been selected.
        if ('liabilities' === $data['account_type']) {
            $data['account_type']    = null;
            $data['account_type_id'] = $this->integer('liability_type_id');
        }

        return $data;
    }

    /**
     * Rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        $accountRoles   = implode(',', config('firefly.accountRoles'));
        $types          = implode(',', array_keys(config('firefly.subTitlesByIdentifier')));
        $ccPaymentTypes = implode(',', array_keys(config('firefly.ccTypes')));
        $rules          = [
            'name'                               => 'required|min:1|uniqueAccountForUser',
            'opening_balance'                    => 'numeric|required_with:opening_balance_date|nullable',
            'opening_balance_date'               => 'date|required_with:opening_balance|nullable',
            'iban'                               => ['iban', 'nullable', new UniqueIban(null, $this->string('what'))],
            'BIC'                                => 'bic|nullable',
            'virtual_balance'                    => 'numeric|nullable',
            'currency_id'                        => 'exists:transaction_currencies,id',
            'account_number'                     => 'between:1,255|uniqueAccountNumberForUser|nullable',
            'account_role'                       => 'in:' . $accountRoles,
            'active'                             => 'boolean',
            'ccType'                             => 'in:' . $ccPaymentTypes,
            'cc_monthly_payment_date'            => 'date',
            'amount_currency_id_opening_balance' => 'exists:transaction_currencies,id',
            'amount_currency_id_virtua_balance'  => 'exists:transaction_currencies,id',
            'what'                               => 'in:' . $types,
            'interest_period'                    => 'in:daily,monthly,yearly',
        ];

        // TODO verify if this will work.
        if ('liabilities' === $this->get('what')) {
            $rules['opening_balance']      = ['numeric', 'required', new ZeroOrMore];
            $rules['opening_balance_date'] = 'date|required';
        }

        /** @var Account $account */
        $account = $this->route()->parameter('account');
        if (null !== $account) {
            // add rules:
            $rules['id']   = 'belongsToUser:accounts';
            $rules['name'] = 'required|min:1|uniqueAccountForUser:' . $account->id;
            $rules['iban'] = ['iban', 'nullable', new UniqueIban($account, $account->accountType->type)];
        }

        return $rules;
    }
}
