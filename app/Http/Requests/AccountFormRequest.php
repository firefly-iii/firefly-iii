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
            'name'                 => $this->string('name'),
            'active'               => $this->boolean('active'),
            'accountType'          => $this->string('what'),
            'account_type_id'      => 0,
            'currency_id'          => $this->integer('currency_id'),
            'virtualBalance'       => $this->string('virtualBalance'),
            'iban'                 => $this->string('iban'),
            'BIC'                  => $this->string('BIC'),
            'accountNumber'        => $this->string('accountNumber'),
            'accountRole'          => $this->string('accountRole'),
            'openingBalance'       => $this->string('openingBalance'),
            'openingBalanceDate'   => $this->date('openingBalanceDate'),
            'ccType'               => $this->string('ccType'),
            'ccMonthlyPaymentDate' => $this->string('ccMonthlyPaymentDate'),
            'notes'                => $this->string('notes'),
            'interest'             => $this->string('interest'),
            'interest_period'      => $this->string('interest_period'),
            'include_net_worth'    => '1',
        ];
        if (false === $this->boolean('include_net_worth')) {
            $data['include_net_worth'] = '0';
        }

        // if the account type is "liabilities" there are actually four types of liability
        // that could have been selected.
        if ('liabilities' === $data['accountType']) {
            $data['accountType']     = null;
            $data['account_type_id'] = $this->integer('liability_type_id');
            // also reverse the opening balance:
            if ('' !== $data['openingBalance']) {
                $data['openingBalance'] = bcmul($data['openingBalance'], '-1');
            }
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
            'name'                              => 'required|min:1|uniqueAccountForUser',
            'openingBalance'                    => 'numeric|required_with:openingBalanceDate|nullable',
            'openingBalanceDate'                => 'date|required_with:openingBalance|nullable',
            'iban'                              => ['iban', 'nullable', new UniqueIban(null, $this->string('what'))],
            'BIC'                               => 'bic|nullable',
            'virtualBalance'                    => 'numeric|nullable',
            'currency_id'                       => 'exists:transaction_currencies,id',
            'accountNumber'                     => 'between:1,255|uniqueAccountNumberForUser|nullable',
            'accountRole'                       => 'in:' . $accountRoles,
            'active'                            => 'boolean',
            'ccType'                            => 'in:' . $ccPaymentTypes,
            'ccMonthlyPaymentDate'              => 'date',
            'amount_currency_id_openingBalance' => 'exists:transaction_currencies,id',
            'amount_currency_id_virtualBalance' => 'exists:transaction_currencies,id',
            'what'                              => 'in:' . $types,
            'interest_period'                   => 'in:daily,monthly,yearly',
        ];

        if ('liabilities' === $this->get('what')) {
            $rules['openingBalance']     = 'numeric|required|more:0';
            $rules['openingBalanceDate'] = 'date|required';
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
