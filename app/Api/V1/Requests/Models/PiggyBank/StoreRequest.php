<?php

/**
 * PiggyBankStoreRequest.php
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

namespace FireflyIII\Api\V1\Requests\Models\PiggyBank;

use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Rules\IsValidPositiveAmount;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Class StoreRequest
 */
class StoreRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        $fields                            = [
            'order' => ['order', 'convertInteger'],
        ];
        $data                              = $this->getAllData($fields);
        $data['name']                      = $this->convertString('name');
        $data['accounts']                  = $this->parseAccounts($this->get('accounts'));
        $data['target_amount']             = $this->convertString('target_amount');
        $data['start_date']                = $this->getCarbonDate('start_date');
        $data['target_date']               = $this->getCarbonDate('target_date');
        $data['notes']                     = $this->stringWithNewlines('notes');
        $data['object_group_id']           = $this->convertInteger('object_group_id');
        $data['transaction_currency_id']   = $this->convertInteger('transaction_currency_id');
        $data['transaction_currency_code'] = $this->convertString('transaction_currency_code');
        $data['object_group_title']        = $this->convertString('object_group_title');

        return $data;
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        return [
            'name'                      => 'required|min:1|max:255|uniquePiggyBankForUser',
            'accounts'                  => 'required',
            'accounts.*'                => 'array|required',
            'accounts.*.account_id'     => 'required|numeric|belongsToUser:accounts,id',
            'accounts.*.current_amount' => ['numeric', new IsValidPositiveAmount()],
            'object_group_id'           => 'numeric|belongsToUser:object_groups,id',
            'object_group_title'        => ['min:1', 'max:255'],
            'target_amount'             => ['required', new IsValidPositiveAmount()],
            'start_date'                => 'date|nullable',
            'transaction_currency_id'   => 'exists:transaction_currencies,id',
            'transaction_currency_code' => 'exists:transaction_currencies,code',
            'target_date'               => 'date|nullable|after:start_date',
            'notes'                     => 'max:65000',
        ];
    }

    /**
     * Can only store money on liabilities and asset accouns.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                // validate start before end only if both are there.
                $data = $validator->getData();
                if (array_key_exists('accounts', $data) && is_array($data['accounts'])) {
                    $repository = app(AccountRepositoryInterface::class);
                    $types      = config('firefly.piggy_bank_account_types');
                    foreach ($data['accounts'] as $index => $array) {
                        $accountId = (int) ($array['account_id'] ?? 0);
                        $account   = $repository->find($accountId);
                        if (null !== $account) {
                            $type = $account->accountType->type;
                            if (!in_array($type, $types, true)) {
                                $validator->errors()->add(sprintf('accounts.%d', $index), trans('validation.invalid_account_type'));
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

    private function parseAccounts(mixed $array): array
    {
        if (!is_array($array)) {
            return [];
        }
        $return = [];
        foreach ($array as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $return[] = [
                'account_id'     => $this->integerFromValue((string)($entry['account_id'] ?? '0')),
                'current_amount' => $this->clearString($entry['current_amount'] ?? '0'),
            ];
        }
        return $return;
    }
}
