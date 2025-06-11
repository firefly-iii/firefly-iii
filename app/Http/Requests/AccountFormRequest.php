<?php

/**
 * AccountFormRequest.php
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

namespace FireflyIII\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\Account;
use FireflyIII\Models\Location;
use FireflyIII\Rules\IsValidAmount;
use FireflyIII\Rules\UniqueIban;
use FireflyIII\Support\Request\AppendsLocationData;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * Class AccountFormRequest.
 */
class AccountFormRequest extends FormRequest
{
    use AppendsLocationData;
    use ChecksLogin;
    use ConvertsDataTypes;

    protected array $acceptedRoles = [UserRoleEnum::MANAGE_TRANSACTIONS];

    /**
     * Get all data.
     */
    public function getAccountData(): array
    {
        $data = [
            'name'                    => $this->convertString('name'),
            'active'                  => $this->boolean('active'),
            'account_type_name'       => $this->convertString('objectType'),
            'currency_id'             => $this->convertInteger('currency_id'),
            'virtual_balance'         => $this->convertString('virtual_balance'),
            'iban'                    => $this->convertIban('iban'),
            'BIC'                     => $this->convertString('BIC'),
            'account_number'          => $this->convertString('account_number'),
            'account_role'            => $this->convertString('account_role'),
            'opening_balance'         => $this->convertString('opening_balance'),
            'opening_balance_date'    => $this->getCarbonDate('opening_balance_date'),
            'cc_type'                 => $this->convertString('cc_type'),
            'cc_monthly_payment_date' => $this->convertString('cc_monthly_payment_date'),
            'notes'                   => $this->stringWithNewlines('notes'),
            'interest'                => $this->convertString('interest'),
            'interest_period'         => $this->convertString('interest_period'),
            'include_net_worth'       => '1',
            'liability_direction'     => $this->convertString('liability_direction'),
        ];

        $data = $this->appendLocationData($data, 'location');
        if (false === $this->boolean('include_net_worth')) {
            $data['include_net_worth'] = '0';
        }
        if ('0' === $data['opening_balance']) {
            $data['opening_balance'] = '';
        }

        // if the account type is "liabilities" there are actually four types of liability
        // that could have been selected.
        if ('liabilities' === $data['account_type_name']) {
            $data['account_type_name'] = null;
            $data['account_type_id']   = $this->convertInteger('liability_type_id');
            if ('' !== $data['opening_balance']) {
                // opening balance is always positive for liabilities
                $data['opening_balance'] = app('steam')->positive($data['opening_balance']);
            }
        }

        return $data;
    }

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        $accountRoles   = implode(',', config('firefly.accountRoles'));
        $types          = implode(',', array_keys(config('firefly.subTitlesByIdentifier')));
        $ccPaymentTypes = implode(',', array_keys(config('firefly.ccTypes')));
        $rules          = [
            'name'                               => 'required|max:1024|min:1|uniqueAccountForUser',
            'opening_balance'                    => ['nullable', new IsValidAmount()],
            'opening_balance_date'               => 'date|required_with:opening_balance|nullable',
            'iban'                               => ['iban', 'nullable', new UniqueIban(null, $this->convertString('objectType'))],
            'BIC'                                => 'bic|nullable',
            'virtual_balance'                    => ['nullable', new IsValidAmount()],
            'currency_id'                        => 'exists:transaction_currencies,id',
            'account_number'                     => 'min:1|max:255|uniqueAccountNumberForUser|nullable',
            'account_role'                       => 'in:'.$accountRoles,
            'active'                             => 'boolean',
            'cc_type'                            => 'in:'.$ccPaymentTypes,
            'amount_currency_id_opening_balance' => 'exists:transaction_currencies,id',
            'amount_currency_id_virtual_balance' => 'exists:transaction_currencies,id',
            'what'                               => 'in:'.$types,
            'interest_period'                    => 'in:daily,monthly,yearly',
            'notes'                              => 'min:1|max:32768|nullable',
        ];
        $rules          = Location::requestRules($rules);

        /** @var null|Account $account */
        $account        = $this->route()->parameter('account');
        if (null !== $account) {
            // add rules:
            $rules['id']   = 'belongsToUser:accounts';
            $rules['name'] = 'required|max:1024|min:1|uniqueAccountForUser:'.$account->id;
            $rules['iban'] = ['iban', 'nullable', new UniqueIban($account, $account->accountType->type)];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
