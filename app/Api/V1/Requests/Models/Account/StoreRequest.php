<?php

/*
 * AccountStoreRequest.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Requests\Models\Account;

use FireflyIII\Models\Location;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Rules\UniqueAccountNumber;
use FireflyIII\Rules\UniqueIban;
use FireflyIII\Support\Request\AppendsLocationData;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreRequest
 */
class StoreRequest extends FormRequest
{
    use AppendsLocationData;
    use ChecksLogin;
    use ConvertsDataTypes;

    public function getAllAccountData(): array
    {
        $active          = true;
        $includeNetWorth = true;
        if (null !== $this->get('active')) {
            $active = $this->boolean('active');
        }
        if (null !== $this->get('include_net_worth')) {
            $includeNetWorth = $this->boolean('include_net_worth');
        }
        $data = [
            'name'                    => $this->convertString('name'),
            'active'                  => $active,
            'include_net_worth'       => $includeNetWorth,
            'account_type_name'       => $this->convertString('type'),
            'account_type_id'         => null,
            'currency_id'             => $this->convertInteger('currency_id'),
            'order'                   => $this->convertInteger('order'),
            'currency_code'           => $this->convertString('currency_code'),
            'virtual_balance'         => $this->convertString('virtual_balance'),
            'iban'                    => $this->convertString('iban'),
            'BIC'                     => $this->convertString('bic'),
            'account_number'          => $this->convertString('account_number'),
            'account_role'            => $this->convertString('account_role'),
            'opening_balance'         => $this->convertString('opening_balance'),
            'opening_balance_date'    => $this->getCarbonDate('opening_balance_date'),
            'cc_type'                 => $this->convertString('credit_card_type'),
            'cc_monthly_payment_date' => $this->convertString('monthly_payment_date'),
            'notes'                   => $this->stringWithNewlines('notes'),
            'interest'                => $this->convertString('interest'),
            'interest_period'         => $this->convertString('interest_period'),
        ];
        // append location information.
        $data = $this->appendLocationData($data, null);

        if ('liability' === $data['account_type_name'] || 'liabilities' === $data['account_type_name']) {
            $data['account_type_name']   = $this->convertString('liability_type');
            $data['liability_direction'] = $this->convertString('liability_direction');
            $data['account_type_id']     = null;
        }

        return $data;
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        $accountRoles   = implode(',', config('firefly.accountRoles'));
        $types          = implode(',', array_keys(config('firefly.subTitlesByIdentifier')));
        $ccPaymentTypes = implode(',', array_keys(config('firefly.ccTypes')));
        $type           = $this->convertString('type');
        $rules          = [
            'name'                 => 'required|max:1024|min:1|uniqueAccountForUser',
            'type'                 => 'required|max:1024|min:1|'.sprintf('in:%s', $types),
            'iban'                 => ['iban', 'nullable', new UniqueIban(null, $type)],
            'bic'                  => 'bic|nullable',
            'account_number'       => ['between:1,255', 'nullable', new UniqueAccountNumber(null, $type)],
            'opening_balance'      => 'numeric|required_with:opening_balance_date|nullable',
            'opening_balance_date' => 'date|required_with:opening_balance|nullable',
            'virtual_balance'      => 'numeric|nullable',
            'order'                => 'numeric|nullable',
            'currency_id'          => 'numeric|exists:transaction_currencies,id',
            'currency_code'        => 'min:3|max:3|exists:transaction_currencies,code',
            'active'               => [new IsBoolean()],
            'include_net_worth'    => [new IsBoolean()],
            'account_role'         => sprintf('nullable|in:%s|required_if:type,asset', $accountRoles),
            'credit_card_type'     => sprintf('nullable|in:%s|required_if:account_role,ccAsset', $ccPaymentTypes),
            'monthly_payment_date' => 'nullable|date|required_if:account_role,ccAsset|required_if:credit_card_type,monthlyFull',
            'liability_type'       => 'nullable|required_if:type,liability|required_if:type,liabilities|in:loan,debt,mortgage',
            'liability_amount'     => 'required_with:liability_start_date|min:0|numeric|max:1000000000',
            'liability_start_date' => 'required_with:liability_amount|date',
            'liability_direction'  => 'nullable|required_if:type,liability|required_if:type,liabilities|in:credit,debit',
            'interest'             => 'between:0,100|numeric',
            'interest_period'      => sprintf('nullable|in:%s', implode(',', config('firefly.interest_periods'))),
            'notes'                => 'min:0|max:65536',
        ];

        return Location::requestRules($rules);
    }
}
