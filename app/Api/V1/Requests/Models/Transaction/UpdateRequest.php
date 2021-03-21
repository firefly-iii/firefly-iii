<?php

/**
 * TransactionUpdateRequest.php
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

namespace FireflyIII\Api\V1\Requests\Models\Transaction;

use FireflyIII\Models\TransactionGroup;
use FireflyIII\Rules\BelongsUser;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Rules\IsDateOrTime;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\Validation\GroupValidation;
use FireflyIII\Validation\TransactionValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Log;

/**
 * Class UpdateRequest
 */
class UpdateRequest extends FormRequest
{
    use TransactionValidation, GroupValidation, ConvertsDataTypes, ChecksLogin;

    private array $arrayFields;
    private array $booleanFields;
    private array $dateFields;
    private array $integerFields;
    private array $stringFields;
    private array $textareaFields;

    /**
     * Get all data. Is pretty complex because of all the ??-statements.
     *
     * @return array
     */
    public function getAll(): array
    {
        $this->integerFields = [
            'order',
            'currency_id',
            'foreign_currency_id',
            'transaction_journal_id',
            'source_id',
            'destination_id',
            'budget_id',
            'category_id',
            'bill_id',
            'recurrence_id',
        ];

        $this->dateFields = [
            'date',
            'interest_date',
            'book_date',
            'process_date',
            'due_date',
            'payment_date',
            'invoice_date',
        ];

        $this->textareaFields = [
            'notes',
        ];

        $this->stringFields  = [
            'type',
            'currency_code',
            'foreign_currency_code',
            'amount',
            'foreign_amount',
            'description',
            'source_name',
            'source_iban',
            'source_number',
            'source_bic',
            'destination_name',
            'destination_iban',
            'destination_number',
            'destination_bic',
            'budget_name',
            'category_name',
            'bill_name',
            'internal_reference',
            'external_id',
            'bunq_payment_id',
            'sepa_cc',
            'sepa_ct_op',
            'sepa_ct_id',
            'sepa_db',
            'sepa_country',
            'sepa_ep',
            'sepa_ci',
            'sepa_batch_id',
            'external_uri',
        ];
        $this->booleanFields = [
            'reconciled',
        ];

        $this->arrayFields = [
            'tags',
        ];
        $data              = [];
        if ($this->has('transactions')) {
            $data['transactions'] = $this->getTransactionData();
        }
        if ($this->has('apply_rules')) {
            $data['apply_rules'] = $this->boolean('apply_rules', true);
        }
        if ($this->has('group_title')) {
            $data['group_title'] = $this->string('group_title');
        }

        return $data;
    }

    /**
     * Get transaction data.
     *
     * @return array
     */
    private function getTransactionData(): array
    {
        Log::debug('Now in getTransactionData()');
        $return = [];

        if (!is_countable($this->get('transactions'))) {
            return $return;
        }

        /**
         * @var int   $index
         * @var array $transaction
         */
        foreach ($this->get('transactions') as $transaction) {
            // default response is to update nothing in the transaction:
            $current  = [];
            $current  = $this->getIntegerData($current, $transaction);
            $current  = $this->getStringData($current, $transaction);
            $current  = $this->getNlStringData($current, $transaction);
            $current  = $this->getDateData($current, $transaction);
            $current  = $this->getBooleanData($current, $transaction);
            $current  = $this->getArrayData($current, $transaction);
            $return[] = $current;
        }

        return $return;
    }

    /**
     * For each field, add it to the array if a reference is present in the request:
     *
     * @param array $current
     *
     * @return array
     */
    private function getIntegerData(array $current, array $transaction): array
    {
        foreach ($this->integerFields as $fieldName) {
            if (array_key_exists($fieldName, $transaction)) {
                $current[$fieldName] = $this->integerFromValue((string)$transaction[$fieldName]);
            }
        }

        return $current;
    }

    /**
     * @param array $current
     * @param array $transaction
     *
     * @return array
     */
    private function getStringData(array $current, array $transaction): array
    {
        foreach ($this->stringFields as $fieldName) {
            if (array_key_exists($fieldName, $transaction)) {
                $current[$fieldName] = $this->stringFromValue((string)$transaction[$fieldName]);
            }
        }

        return $current;
    }

    /**
     * @param array $current
     * @param array $transaction
     *
     * @return array
     */
    private function getNlStringData(array $current, array $transaction): array
    {
        foreach ($this->textareaFields as $fieldName) {
            if (array_key_exists($fieldName, $transaction)) {
                $current[$fieldName] = $this->nlStringFromValue((string)$transaction[$fieldName]);
            }
        }

        return $current;
    }

    /**
     * @param array $current
     * @param array $transaction
     *
     * @return array
     */
    private function getDateData(array $current, array $transaction): array
    {
        foreach ($this->dateFields as $fieldName) {
            Log::debug(sprintf('Now at date field %s', $fieldName));
            if (array_key_exists($fieldName, $transaction)) {
                Log::debug(sprintf('New value: "%s"', (string)$transaction[$fieldName]));
                $current[$fieldName] = $this->dateFromValue((string)$transaction[$fieldName]);
            }
        }

        return $current;
    }

    /**
     * @param array $current
     * @param array $transaction
     *
     * @return array
     */
    private function getBooleanData(array $current, array $transaction): array
    {
        foreach ($this->booleanFields as $fieldName) {
            if (array_key_exists($fieldName, $transaction)) {
                $current[$fieldName] = $this->convertBoolean((string)$transaction[$fieldName]);
            }
        }

        return $current;
    }

    /**
     * @param array $current
     * @param array $transaction
     *
     * @return array
     */
    private function getArrayData(array $current, array $transaction): array
    {
        foreach ($this->arrayFields as $fieldName) {
            if (array_key_exists($fieldName, $transaction)) {
                $current[$fieldName] = $this->arrayFromValue($transaction[$fieldName]);
            }
        }

        return $current;
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // basic fields for group:
            'group_title'                          => 'between:1,1000',
            'apply_rules'                          => [new IsBoolean],

            // transaction rules (in array for splits):
            'transactions.*.type'                  => 'in:withdrawal,deposit,transfer,opening-balance,reconciliation',
            'transactions.*.date'                  => [new IsDateOrTime],
            'transactions.*.order'                 => 'numeric|min:0',

            // currency info
            'transactions.*.currency_id'           => 'numeric|exists:transaction_currencies,id',
            'transactions.*.currency_code'         => 'min:3|max:3|exists:transaction_currencies,code',
            'transactions.*.foreign_currency_id'   => 'nullable|numeric|exists:transaction_currencies,id',
            'transactions.*.foreign_currency_code' => 'nullable|min:3|max:3|exists:transaction_currencies,code',

            // amount
            'transactions.*.amount'                => 'numeric|gt:0|max:100000000000',
            'transactions.*.foreign_amount'        => 'nullable|numeric|gte:0',

            // description
            'transactions.*.description'           => 'nullable|between:1,1000',

            // source of transaction
            'transactions.*.source_id'             => ['numeric', 'nullable', new BelongsUser],
            'transactions.*.source_name'           => 'between:1,255|nullable',

            // destination of transaction
            'transactions.*.destination_id'        => ['numeric', 'nullable', new BelongsUser],
            'transactions.*.destination_name'      => 'between:1,255|nullable',

            // budget, category, bill and piggy
            'transactions.*.budget_id'             => ['mustExist:budgets,id', new BelongsUser],
            'transactions.*.budget_name'           => ['between:1,255', 'nullable', new BelongsUser],
            'transactions.*.category_id'           => ['mustExist:categories,id', new BelongsUser],
            'transactions.*.category_name'         => 'between:1,255|nullable',
            'transactions.*.bill_id'               => ['numeric', 'nullable', 'mustExist:bills,id', new BelongsUser],
            'transactions.*.bill_name'             => ['between:1,255', 'nullable', new BelongsUser],

            // other interesting fields
            'transactions.*.reconciled'            => [new IsBoolean],
            'transactions.*.notes'                 => 'min:1,max:50000|nullable',
            'transactions.*.tags'                  => 'between:0,255',

            // meta info fields
            'transactions.*.internal_reference'    => 'min:1,max:255|nullable',
            'transactions.*.external_id'           => 'min:1,max:255|nullable',
            'transactions.*.recurrence_id'         => 'min:1,max:255|nullable',
            'transactions.*.bunq_payment_id'       => 'min:1,max:255|nullable',
            'transactions.*.external_uri'          => 'min:1,max:255|nullable|url',

            // SEPA fields:
            'transactions.*.sepa_cc'               => 'min:1,max:255|nullable',
            'transactions.*.sepa_ct_op'            => 'min:1,max:255|nullable',
            'transactions.*.sepa_ct_id'            => 'min:1,max:255|nullable',
            'transactions.*.sepa_db'               => 'min:1,max:255|nullable',
            'transactions.*.sepa_country'          => 'min:1,max:255|nullable',
            'transactions.*.sepa_ep'               => 'min:1,max:255|nullable',
            'transactions.*.sepa_ci'               => 'min:1,max:255|nullable',
            'transactions.*.sepa_batch_id'         => 'min:1,max:255|nullable',

            // dates
            'transactions.*.interest_date'         => 'date|nullable',
            'transactions.*.book_date'             => 'date|nullable',
            'transactions.*.process_date'          => 'date|nullable',
            'transactions.*.due_date'              => 'date|nullable',
            'transactions.*.payment_date'          => 'date|nullable',
            'transactions.*.invoice_date'          => 'date|nullable',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        /** @var TransactionGroup $transactionGroup */
        $transactionGroup = $this->route()->parameter('transactionGroup');
        $validator->after(
            function (Validator $validator) use ($transactionGroup) {
                // if more than one, verify that there are journal ID's present.
                $this->validateJournalIds($validator, $transactionGroup);

                // all transaction types must be equal:
                $this->validateTransactionTypesForUpdate($validator, $transactionGroup);

                // validate source/destination is equal, depending on the transaction journal type.
                $this->validateEqualAccountsForUpdate($validator, $transactionGroup);

                // validate that the currency fits the source and/or destination account.
                // validate all account info
                $this->validateAccountInformationUpdate($validator, $transactionGroup);

            }
        );
    }
}
