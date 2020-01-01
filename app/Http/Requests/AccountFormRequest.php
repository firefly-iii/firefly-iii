<?php
/**
 * AccountFormRequest.php
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

namespace FireflyIII\Http\Requests;

use FireflyIII\Models\Account;
use FireflyIII\Models\Location;
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
            'name'                    => $this->string('name'),
            'active'                  => $this->boolean('active'),
            'account_type'            => $this->string('objectType'),
            'account_type_id'         => 0,
            'currency_id'             => $this->integer('currency_id'),
            'virtual_balance'         => $this->string('virtual_balance'),
            'iban'                    => $this->string('iban'),
            'BIC'                     => $this->string('BIC'),
            'account_number'          => $this->string('account_number'),
            'account_role'            => $this->string('account_role'),
            'opening_balance'         => $this->string('opening_balance'),
            'opening_balance_date'    => $this->date('opening_balance_date'),
            'cc_type'                 => $this->string('cc_type'),
            'cc_monthly_payment_date' => $this->string('cc_monthly_payment_date'),
            'notes'                   => $this->nlString('notes'),
            'interest'                => $this->string('interest'),
            'interest_period'         => $this->string('interest_period'),
            'include_net_worth'       => '1',

            // new: location
            'longitude'               => $this->string('location_longitude'),
            'latitude'                => $this->string('location_latitude'),
            'zoom_level'              => $this->integer('location_zoom_level'),
            'has_location'            => $this->boolean('location_has_location'),
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
            'opening_balance'                    => 'numeric|required_with:opening_balance_date|nullable|max:1000000000',
            'opening_balance_date'               => 'date|required_with:opening_balance|nullable',
            'iban'                               => ['iban', 'nullable', new UniqueIban(null, $this->string('objectType'))],
            'BIC'                                => 'bic|nullable',
            'virtual_balance'                    => 'numeric|nullable|max:1000000000',
            'currency_id'                        => 'exists:transaction_currencies,id',
            'account_number'                     => 'between:1,255|uniqueAccountNumberForUser|nullable',
            'account_role'                       => 'in:' . $accountRoles,
            'active'                             => 'boolean',
            'cc_type'                            => 'in:' . $ccPaymentTypes,
            'amount_currency_id_opening_balance' => 'exists:transaction_currencies,id',
            'amount_currency_id_virtual_balance' => 'exists:transaction_currencies,id',
            'what'                               => 'in:' . $types,
            'interest_period'                    => 'in:daily,monthly,yearly',
        ];
        $rules = Location::requestRules($rules);

        if ('liabilities' === $this->get('objectType')) {
            $rules['opening_balance']      = ['numeric', 'required','max:1000000000'];
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
