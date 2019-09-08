<?php

/**
 * TransactionUpdateRequest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Api\V1\Requests;

use FireflyIII\Models\TransactionGroup;
use FireflyIII\Rules\BelongsUser;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Rules\IsDateOrTime;
use FireflyIII\Validation\TransactionValidation;
use Illuminate\Validation\Validator;
use Log;

/**
 * Class TransactionUpdateRequest
 */
class TransactionUpdateRequest extends Request
{
    use TransactionValidation;

    /** @var array Array values. */
    private $arrayFields;
    /** @var array Boolean values. */
    private $booleanFields;
    /** @var array Fields that contain date values. */
    private $dateFields;
    /** @var array Fields that contain integer values. */
    private $integerFields;
    /** @var array Fields that contain string values. */
    private $stringFields;

    /**
     * Authorize logged in users.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow authenticated users
        return auth()->check();
    }

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

        $this->stringFields  = [
            'type',
            'currency_code',
            'foreign_currency_code',
            'amount',
            'foreign_amount',
            'description',
            'source_name',
            'destination_name',
            'budget_name',
            'category_name',
            'bill_name',
            'notes',
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
        ];
        $this->booleanFields = [
            'reconciled',
        ];

        $this->arrayFields = [
            'tags',
        ];


        $data = [
            'transactions' => $this->getTransactionData(),
        ];
        if ($this->has('group_title')) {
            $data['group_title'] = $this->string('group_title');
        }

        return $data;
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            // basic fields for group:
            'group_title'                          => 'between:1,255',

            // transaction rules (in array for splits):
            'transactions.*.type'                  => 'in:withdrawal,deposit,transfer,opening-balance,reconciliation',
            'transactions.*.date'                  => [new IsDateOrTime],
            'transactions.*.order'                 => 'numeric|min:0',

            // currency info
            'transactions.*.currency_id'           => 'numeric|exists:transaction_currencies,id',
            'transactions.*.currency_code'         => 'min:3|max:3|exists:transaction_currencies,code',
            'transactions.*.foreign_currency_id'   => 'numeric|exists:transaction_currencies,id',
            'transactions.*.foreign_currency_code' => 'min:3|max:3|exists:transaction_currencies,code',

            // amount
            'transactions.*.amount'                => 'numeric|more:0',
            'transactions.*.foreign_amount'        => 'numeric|gte:0',

            // description
            'transactions.*.description'           => 'nullable|between:1,255',

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

        return $rules;
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
                // must submit at least one transaction.
                $this->validateOneTransaction($validator);

                // if more than one, verify that there are journal ID's present.
                $this->validateJournalIds($validator, $transactionGroup);

                // all transaction types must be equal:
                $this->validateTransactionTypesForUpdate($validator);

                // validate source/destination is equal, depending on the transaction journal type.
                $this->validateEqualAccountsForUpdate($validator, $transactionGroup);

                // If type is set, source + destination info is mandatory.
                // Not going to do this. Not sure where the demand came from.

                // validate that the currency fits the source and/or destination account.
                // validate all account info
                $this->validateAccountInformationUpdate($validator);

                // The currency info must match the accounts involved.
                // Instead will ignore currency info as much as possible.

                // TODO if the transaction_journal_id is empty, some fields are mandatory, like the amount!

                // all journals must have a description
                //$this->validateDescriptions($validator);

                //                // validate foreign currency info
                //                $this->validateForeignCurrencyInformation($validator);
                //

                //
                //                // make sure all splits have valid source + dest info
                //                $this->validateSplitAccounts($validator);
                //                 the group must have a description if > 1 journal.
                //                $this->validateGroupDescription($validator);
            }
        );
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
        /**
         * @var int   $index
         * @var array $transaction
         */
        foreach ($this->get('transactions') as $index => $transaction) {
            // default response is to update nothing in the transaction:
            $current = [];

            // for each field, add it to the array if a reference is present in the request:
            foreach ($this->integerFields as $fieldName) {
                if (array_key_exists($fieldName, $transaction)) {
                    $current[$fieldName] = $this->integerFromValue((string)$transaction[$fieldName]);
                }
            }

            foreach ($this->stringFields as $fieldName) {
                if (array_key_exists($fieldName, $transaction)) {
                    $current[$fieldName] = $this->stringFromValue((string)$transaction[$fieldName]);
                }
            }

            foreach ($this->dateFields as $fieldName) {
                Log::debug(sprintf('Now at date field %s', $fieldName));
                if (array_key_exists($fieldName, $transaction)) {
                    $current[$fieldName] = $this->dateFromValue((string)$transaction[$fieldName]);
                    Log::debug(sprintf('New value: "%s"', (string)$transaction[$fieldName]));
                }
            }

            foreach ($this->booleanFields as $fieldName) {
                if (array_key_exists($fieldName, $transaction)) {
                    $current[$fieldName] = $this->convertBoolean((string)$transaction[$fieldName]);
                }
            }

            foreach ($this->arrayFields as $fieldName) {
                if (array_key_exists($fieldName, $transaction)) {
                    $current[$fieldName] = $this->arrayFromValue($transaction[$fieldName]);
                }
            }
            $return[] = $current;
        }

        return $return;
    }
}
