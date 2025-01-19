<?php

/**
 * PiggyBankUpdateRequest.php
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

use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Rules\IsValidPositiveAmount;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Class PiggyBankFormRequest.
 */
class PiggyBankUpdateRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Returns the data required by the controller.
     */
    public function getPiggyBankData(): array
    {
        $accounts = $this->get('accounts');
        $data     = [
            'name'               => $this->convertString('name'),
            'start_date'         => $this->getCarbonDate('start_date'),
            'target_amount'      => trim($this->convertString('target_amount')),
            'target_date'        => $this->getCarbonDate('target_date'),
            'notes'              => $this->stringWithNewlines('notes'),
            'object_group_title' => $this->convertString('object_group'),
        ];
        if (!is_array($accounts)) {
            $accounts = [];
        }
        foreach ($accounts as $item) {
            $data['accounts'][] = ['account_id' => (int) $item];
        }

        return $data;
    }

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        /** @var PiggyBank $piggy */
        $piggy = $this->route()->parameter('piggyBank');

        return [
            'name'          => sprintf('required|min:1|max:255|uniquePiggyBankForUser:%d', $piggy->id),
            'accounts'      => 'required|array',
            'accounts.*'    => 'required|belongsToUser:accounts',
            'target_amount' => ['nullable', new IsValidPositiveAmount()],
            'start_date'    => 'date',
            'target_date'   => 'date|nullable',
            'order'         => 'integer|max:32768|min:1',
            'object_group'  => 'min:0|max:255',
            'notes'         => 'min:1|max:32768|nullable',
        ];
    }

    public function withValidator(Validator $validator): void
    {        // need to have more than one account.
        // accounts need to have the same currency or be multi-currency(?).
        $validator->after(
            function (Validator $validator): void {
                // validate start before end only if both are there.
                $data     = $validator->getData();
                $currency = $this->getCurrencyFromData($data);
                if (array_key_exists('accounts', $data) && is_array($data['accounts'])) {
                    $repository = app(AccountRepositoryInterface::class);
                    $types      = config('firefly.piggy_bank_account_types');
                    foreach ($data['accounts'] as $value) {
                        $accountId = (int) $value;
                        $account   = $repository->find($accountId);
                        if (null !== $account) {
                            // check currency here.
                            $accountCurrency = $repository->getAccountCurrency($account);
                            $isMultiCurrency = $repository->getMetaValue($account, 'is_multi_currency');
                            if ($accountCurrency->id !== $currency->id && 'true' !== $isMultiCurrency) {
                                $validator->errors()->add('accounts', trans('validation.invalid_account_currency'));
                            }
                            $type            = $account->accountType->type;
                            if (!in_array($type, $types, true)) {
                                $validator->errors()->add('accounts', trans('validation.invalid_account_type'));
                            }
                        }
                    }
                }
            }
        );


        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', __CLASS__), $validator->errors()->toArray());
        }
    }

    private function getCurrencyFromData(array $data): TransactionCurrency
    {
        $currencyId = (int) ($data['transaction_currency_id'] ?? 0);
        $currency   = TransactionCurrency::find($currencyId);
        if (null === $currency) {
            return Amount::getNativeCurrency();
        }

        return $currency;
    }
}
