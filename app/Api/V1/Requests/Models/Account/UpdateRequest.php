<?php

/*
 * AccountUpdateRequest.php
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

use Illuminate\Contracts\Validation\Validator;
use FireflyIII\Models\Account;
use FireflyIII\Models\Location;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Rules\UniqueAccountNumber;
use FireflyIII\Rules\UniqueIban;
use FireflyIII\Support\Request\AppendsLocationData;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * Class UpdateRequest
 */
class UpdateRequest extends FormRequest
{
    use AppendsLocationData;
    use ChecksLogin;
    use ConvertsDataTypes;

    public function getUpdateData(): array
    {
        $fields = [
            'name'                    => ['name', 'convertString'],
            'active'                  => ['active', 'boolean'],
            'include_net_worth'       => ['include_net_worth', 'boolean'],
            'account_type_name'       => ['type', 'convertString'],
            'virtual_balance'         => ['virtual_balance', 'convertString'],
            'iban'                    => ['iban', 'convertIban'],
            'BIC'                     => ['bic', 'convertString'],
            'account_number'          => ['account_number', 'convertString'],
            'account_role'            => ['account_role', 'convertString'],
            'liability_type'          => ['liability_type', 'convertString'],
            'opening_balance'         => ['opening_balance', 'convertString'],
            'opening_balance_date'    => ['opening_balance_date', 'convertDateTime'],
            'cc_type'                 => ['credit_card_type', 'convertString'],
            'cc_monthly_payment_date' => ['monthly_payment_date', 'convertDateTime'],
            'notes'                   => ['notes', 'stringWithNewlines'],
            'interest'                => ['interest', 'convertString'],
            'interest_period'         => ['interest_period', 'convertString'],
            'order'                   => ['order', 'convertInteger'],
            'currency_id'             => ['currency_id', 'convertInteger'],
            'currency_code'           => ['currency_code', 'convertString'],
            'liability_direction'     => ['liability_direction', 'convertString'],
            'liability_amount'        => ['liability_amount', 'convertString'],
            'liability_start_date'    => ['liability_start_date', 'date'],
        ];
        $data   = $this->getAllData($fields);

        return $this->appendLocationData($data, null);
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        /** @var Account $account */
        $account        = $this->route()->parameter('account');
        $accountRoles   = implode(',', config('firefly.accountRoles'));
        $types          = implode(',', array_keys(config('firefly.subTitlesByIdentifier')));
        $ccPaymentTypes = implode(',', array_keys(config('firefly.ccTypes')));

        $rules          = [
            'name'                 => sprintf('min:1|max:1024|uniqueAccountForUser:%d', $account->id),
            'type'                 => sprintf('in:%s', $types),
            'iban'                 => ['iban', 'nullable', new UniqueIban($account, $this->convertString('type'))],
            'bic'                  => 'bic|nullable',
            'account_number'       => ['min:1', 'max:255', 'nullable', new UniqueAccountNumber($account, $this->convertString('type'))],
            'opening_balance'      => 'numeric|required_with:opening_balance_date|nullable',
            'opening_balance_date' => 'date|required_with:opening_balance|nullable',
            'virtual_balance'      => 'numeric|nullable',
            'order'                => 'numeric|nullable',
            'currency_id'          => 'numeric|exists:transaction_currencies,id',
            'currency_code'        => 'min:3|max:51|exists:transaction_currencies,code',
            'active'               => [new IsBoolean()],
            'include_net_worth'    => [new IsBoolean()],
            'account_role'         => sprintf('in:%s|nullable|required_if:type,asset', $accountRoles),
            'credit_card_type'     => sprintf('in:%s|nullable|required_if:account_role,ccAsset', $ccPaymentTypes),
            'monthly_payment_date' => 'date|nullable|required_if:account_role,ccAsset|required_if:credit_card_type,monthlyFull',
            'liability_type'       => 'required_if:type,liability|in:loan,debt,mortgage',
            'liability_direction'  => 'required_if:type,liability|in:credit,debit',
            'interest'             => 'required_if:type,liability|min:0|max:100|numeric',
            'interest_period'      => 'required_if:type,liability|in:daily,monthly,yearly',
            'notes'                => 'min:0|max:32768',
        ];

        return Location::requestRules($rules);
    }

    /**
     * Configure the validator instance with special rules for after the basic validation rules.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                // validate start before end only if both are there.
                $data       = $validator->getData();

                /** @var Account $account */
                $account    = $this->route()->parameter('account');

                /** @var AccountRepositoryInterface $repository */
                $repository = app(AccountRepositoryInterface::class);
                $currency   = $repository->getAccountCurrency($account);

                // how many piggies are attached?
                $piggyBanks = $account->piggyBanks()->count();
                if ($piggyBanks > 0 && array_key_exists('currency_code', $data) && $data['currency_code'] !== $currency->code) {
                    $validator->errors()->add('currency_code', (string) trans('validation.piggy_no_change_currency'));
                }
                if ($piggyBanks > 0 && array_key_exists('currency_id', $data) && (int) $data['currency_id'] !== $currency->id) {
                    $validator->errors()->add('currency_id', (string) trans('validation.piggy_no_change_currency'));
                }
            }
        );
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
