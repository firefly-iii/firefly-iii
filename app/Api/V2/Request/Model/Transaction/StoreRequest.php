<?php

/*
 * StoreRequest.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Request\Model\Transaction;

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\UserGroup;
use FireflyIII\Rules\BelongsUserGroup;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Rules\IsDateOrTime;
use FireflyIII\Rules\IsValidPositiveAmount;
use FireflyIII\Support\NullArrayObject;
use FireflyIII\Support\Request\AppendsLocationData;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\User;
use FireflyIII\Validation\CurrencyValidation;
use FireflyIII\Validation\GroupValidation;
use FireflyIII\Validation\TransactionValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Class StoreRequest
 *
 * TODO this class is basically the same as the v1 request. However, it does not accept
 * TODO models, objects and references that are NOT part of the designated user group (aka administration).
 * TODO this distinction is already made in the CheckLogin trait, where there is also a convenient function
 * TODO to grab the current UserGroup. This code is slightly different from other v2 apis that use
 * TODO the "administration_id", those will have to be updated later on.
 */
class StoreRequest extends FormRequest
{
    use AppendsLocationData;
    use ChecksLogin;

    use ConvertsDataTypes;
    use CurrencyValidation;
    use GroupValidation;
    use TransactionValidation;

    protected array $acceptedRoles = [UserRoleEnum::MANAGE_TRANSACTIONS];

    /**
     * Get all data.
     */
    public function getAll(): array
    {
        app('log')->debug('V2: Get all data in TransactionStoreRequest');

        return [
            'group_title'             => $this->convertString('group_title'),
            'error_if_duplicate_hash' => $this->boolean('error_if_duplicate_hash'),
            'apply_rules'             => $this->boolean('apply_rules', true),
            'fire_webhooks'           => $this->boolean('fire_webhooks', true),
            'transactions'            => $this->getTransactionData(),
        ];
    }

    /**
     * Get transaction data.
     */
    private function getTransactionData(): array
    {
        $return = [];

        /**
         * @var array $transaction
         */
        foreach ($this->get('transactions') as $transaction) {
            $object   = new NullArrayObject($transaction);
            $result   = [
                'type'                  => $this->clearString($object['type']),
                'date'                  => $this->dateFromValue($object['date']),
                'order'                 => $this->integerFromValue((string) $object['order']),

                'currency_id'           => $this->integerFromValue((string) $object['currency_id']),
                'currency_code'         => $this->clearString((string) $object['currency_code']),

                // foreign currency info:
                'foreign_currency_id'   => $this->integerFromValue((string) $object['foreign_currency_id']),
                'foreign_currency_code' => $this->clearString((string) $object['foreign_currency_code']),

                // amount and foreign amount. Cannot be 0.
                'amount'                => $this->clearString((string) $object['amount']),
                'foreign_amount'        => $this->clearString((string) $object['foreign_amount']),

                // description.
                'description'           => $this->clearString($object['description']),

                // source of transaction. If everything is null, assume cash account.
                'source_id'             => $this->integerFromValue((string) $object['source_id']),
                'source_name'           => $this->clearString((string) $object['source_name']),
                'source_iban'           => $this->clearString((string) $object['source_iban']),
                'source_number'         => $this->clearString((string) $object['source_number']),
                'source_bic'            => $this->clearString((string) $object['source_bic']),

                // destination of transaction. If everything is null, assume cash account.
                'destination_id'        => $this->integerFromValue((string) $object['destination_id']),
                'destination_name'      => $this->clearString((string) $object['destination_name']),
                'destination_iban'      => $this->clearString((string) $object['destination_iban']),
                'destination_number'    => $this->clearString((string) $object['destination_number']),
                'destination_bic'       => $this->clearString((string) $object['destination_bic']),

                // budget info
                'budget_id'             => $this->integerFromValue((string) $object['budget_id']),
                'budget_name'           => $this->clearString((string) $object['budget_name']),

                // category info
                'category_id'           => $this->integerFromValue((string) $object['category_id']),
                'category_name'         => $this->clearString((string) $object['category_name']),

                // journal bill reference. Optional. Will only work for withdrawals
                'bill_id'               => $this->integerFromValue((string) $object['bill_id']),
                'bill_name'             => $this->clearString((string) $object['bill_name']),

                // piggy bank reference. Optional. Will only work for transfers
                'piggy_bank_id'         => $this->integerFromValue((string) $object['piggy_bank_id']),
                'piggy_bank_name'       => $this->clearString((string) $object['piggy_bank_name']),

                // some other interesting properties
                'reconciled'            => $this->convertBoolean((string) $object['reconciled']),
                'notes'                 => $this->clearStringKeepNewlines((string) $object['notes']),
                'tags'                  => $this->arrayFromValue($object['tags']),

                // all custom fields:
                'internal_reference'    => $this->clearString((string) $object['internal_reference']),
                'external_id'           => $this->clearString((string) $object['external_id']),
                'original_source'       => sprintf('ff3-v%s', config('firefly.version')),
                'recurrence_id'         => $this->integerFromValue($object['recurrence_id']),
                'bunq_payment_id'       => $this->clearString((string) $object['bunq_payment_id']),
                'external_url'          => $this->clearString((string) $object['external_url']),

                'sepa_cc'               => $this->clearString((string) $object['sepa_cc']),
                'sepa_ct_op'            => $this->clearString((string) $object['sepa_ct_op']),
                'sepa_ct_id'            => $this->clearString((string) $object['sepa_ct_id']),
                'sepa_db'               => $this->clearString((string) $object['sepa_db']),
                'sepa_country'          => $this->clearString((string) $object['sepa_country']),
                'sepa_ep'               => $this->clearString((string) $object['sepa_ep']),
                'sepa_ci'               => $this->clearString((string) $object['sepa_ci']),
                'sepa_batch_id'         => $this->clearString((string) $object['sepa_batch_id']),
                // custom date fields. Must be Carbon objects. Presence is optional.
                'interest_date'         => $this->dateFromValue($object['interest_date']),
                'book_date'             => $this->dateFromValue($object['book_date']),
                'process_date'          => $this->dateFromValue($object['process_date']),
                'due_date'              => $this->dateFromValue($object['due_date']),
                'payment_date'          => $this->dateFromValue($object['payment_date']),
                'invoice_date'          => $this->dateFromValue($object['invoice_date']),
            ];
            $result   = $this->addFromromTransactionStore($transaction, $result);
            $return[] = $result;
        }

        return $return;
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        app('log')->debug('V2: Collect rules of TransactionStoreRequest');

        // at this point the userGroup can't be NULL because the
        // authorize() method will complain. Loudly.
        /** @var UserGroup $userGroup */
        $userGroup = $this->getUserGroup();

        return [
            // basic fields for group:
            'group_title'                          => 'min:1|max:1000|nullable',
            'error_if_duplicate_hash'              => [new IsBoolean()],
            'apply_rules'                          => [new IsBoolean()],

            // transaction rules (in array for splits):
            'transactions.*.type'                  => 'required|in:withdrawal,deposit,transfer,opening-balance,reconciliation',
            'transactions.*.date'                  => ['required', new IsDateOrTime()],
            'transactions.*.order'                 => 'numeric|min:0',

            // currency info
            'transactions.*.currency_id'           => 'numeric|exists:transaction_currencies,id|nullable',
            'transactions.*.currency_code'         => 'min:3|max:51|exists:transaction_currencies,code|nullable',
            'transactions.*.foreign_currency_id'   => 'numeric|exists:transaction_currencies,id|nullable',
            'transactions.*.foreign_currency_code' => 'min:3|max:51|exists:transaction_currencies,code|nullable',

            // amount
            'transactions.*.amount'                => ['required', new IsValidPositiveAmount()],
            'transactions.*.foreign_amount'        => ['nullable', new IsValidPositiveAmount()],

            // description
            'transactions.*.description'           => 'nullable|min:1|max:1000',

            // source of transaction
            'transactions.*.source_id'             => ['numeric', 'nullable', new BelongsUserGroup($userGroup)],
            'transactions.*.source_name'           => 'min:1|max:255|nullable',
            'transactions.*.source_iban'           => 'min:1|max:255|nullable|iban',
            'transactions.*.source_number'         => 'min:1|max:255|nullable',
            'transactions.*.source_bic'            => 'min:1|max:255|nullable|bic',

            // destination of transaction
            'transactions.*.destination_id'        => ['numeric', 'nullable', new BelongsUserGroup($userGroup)],
            'transactions.*.destination_name'      => 'min:1|max:255|nullable',
            'transactions.*.destination_iban'      => 'min:1|max:255|nullable|iban',
            'transactions.*.destination_number'    => 'min:1|max:255|nullable',
            'transactions.*.destination_bic'       => 'min:1|max:255|nullable|bic',

            // budget, category, bill and piggy
            'transactions.*.budget_id'             => ['mustExist:budgets,id', new BelongsUserGroup($userGroup)],
            'transactions.*.budget_name'           => ['min:1', 'max:255', 'nullable', new BelongsUserGroup($userGroup)],
            'transactions.*.category_id'           => ['mustExist:categories,id', new BelongsUserGroup($userGroup), 'nullable'],
            'transactions.*.category_name'         => 'min:1|max:255|nullable',
            'transactions.*.bill_id'               => ['numeric', 'nullable', 'mustExist:bills,id', new BelongsUserGroup($userGroup)],
            'transactions.*.bill_name'             => ['min:1', 'max:255', 'nullable', new BelongsUserGroup($userGroup)],
            'transactions.*.piggy_bank_id'         => ['numeric', 'nullable', 'mustExist:piggy_banks,id', new BelongsUserGroup($userGroup)],
            'transactions.*.piggy_bank_name'       => ['min:1', 'max:255', 'nullable', new BelongsUserGroup($userGroup)],

            // other interesting fields
            'transactions.*.reconciled'            => [new IsBoolean()],
            'transactions.*.notes'                 => 'min:1|max:32768|nullable',
            'transactions.*.tags'                  => 'min:0|max:255',
            'transactions.*.tags.*'                => 'min:0|max:255',

            // meta info fields
            'transactions.*.internal_reference'    => 'min:1|max:255|nullable',
            'transactions.*.external_id'           => 'min:1|max:255|nullable',
            'transactions.*.recurrence_id'         => 'min:1|max:255|nullable',
            'transactions.*.bunq_payment_id'       => 'min:1|max:255|nullable',
            'transactions.*.external_url'          => 'min:1|max:255|nullable|url',

            // SEPA fields:
            'transactions.*.sepa_cc'               => 'min:1|max:255|nullable',
            'transactions.*.sepa_ct_op'            => 'min:1|max:255|nullable',
            'transactions.*.sepa_ct_id'            => 'min:1|max:255|nullable',
            'transactions.*.sepa_db'               => 'min:1|max:255|nullable',
            'transactions.*.sepa_country'          => 'min:1|max:255|nullable',
            'transactions.*.sepa_ep'               => 'min:1|max:255|nullable',
            'transactions.*.sepa_ci'               => 'min:1|max:255|nullable',
            'transactions.*.sepa_batch_id'         => 'min:1|max:255|nullable',

            // dates
            'transactions.*.interest_date'         => 'date|nullable',
            'transactions.*.book_date'             => 'date|nullable',
            'transactions.*.process_date'          => 'date|nullable',
            'transactions.*.due_date'              => 'date|nullable',
            'transactions.*.payment_date'          => 'date|nullable',
            'transactions.*.invoice_date'          => 'date|nullable',

            // TODO include location and ability to process it.
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        /** @var User $user */
        $user      = auth()->user();

        /** @var UserGroup $userGroup */
        $userGroup = $this->getUserGroup();
        $validator->after(
            function (Validator $validator) use ($user, $userGroup): void {
                // must be valid array.
                $this->validateTransactionArray($validator); // does not need group validation.

                // must submit at least one transaction.
                app('log')->debug('Now going to validateOneTransaction');
                $this->validateOneTransaction($validator);              // does not need group validation.
                app('log')->debug('Now done with validateOneTransaction');

                // all journals must have a description
                $this->validateDescriptions($validator);                // does not need group validation.

                // all transaction types must be equal:
                $this->validateTransactionTypes($validator);            // does not need group validation.

                // validate foreign currency info
                $this->validateForeignCurrencyInformation($validator);  // does not need group validation.

                // validate all account info
                $this->validateAccountInformation($validator, $user, $userGroup);

                // validate source/destination is equal, depending on the transaction journal type.
                $this->validateEqualAccounts($validator);

                // the group must have a description if > 1 journal.
                $this->validateGroupDescription($validator);
            }
        );
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', __CLASS__), $validator->errors()->toArray());
        }
    }
}
