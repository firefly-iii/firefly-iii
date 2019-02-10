<?php

/**
 * TransactionRequest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

use FireflyIII\Rules\BelongsUser;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Rules\IsDateOrTime;
use FireflyIII\Validation\TransactionValidation;
use Illuminate\Validation\Validator;


/**
 * Class TransactionRequest
 */
class TransactionRequest extends Request
{
    use TransactionValidation;

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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @return array
     */
    public function getAll(): array
    {
        $data = [
            'type'               => $this->string('type'),
            'date'               => $this->dateTime('date'),
            'description'        => $this->string('description'),
            'piggy_bank_id'      => $this->integer('piggy_bank_id'),
            'piggy_bank_name'    => $this->string('piggy_bank_name'),
            'bill_id'            => $this->integer('bill_id'),
            'bill_name'          => $this->string('bill_name'),
            'tags'               => explode(',', $this->string('tags')),
            'notes'              => $this->string('notes'),
            'sepa-cc'            => $this->string('sepa_cc'),
            'sepa-ct-op'         => $this->string('sepa_ct_op'),
            'sepa-ct-id'         => $this->string('sepa_ct_id'),
            'sepa-db'            => $this->string('sepa_db'),
            'sepa-country'       => $this->string('sepa_country'),
            'sepa-ep'            => $this->string('sepa_ep'),
            'sepa-ci'            => $this->string('sepa_ci'),
            'sepa-batch-id'      => $this->string('sepa_batch_id'),
            'interest_date'      => $this->date('interest_date'),
            'book_date'          => $this->date('book_date'),
            'process_date'       => $this->date('process_date'),
            'due_date'           => $this->date('due_date'),
            'payment_date'       => $this->date('payment_date'),
            'invoice_date'       => $this->date('invoice_date'),
            'internal_reference' => $this->string('internal_reference'),
            'bunq_payment_id'    => $this->string('bunq_payment_id'),
            'external_id'        => $this->string('external_id'),
            'original-source'    => sprintf('ff3-v%s|api-v%s', config('firefly.version'), config('firefly.api_version')),
            'transactions'       => $this->getTransactionData(),
        ];

        return $data;
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function rules(): array
    {
        $rules = [
            // basic fields for journal:
            'type'                                 => 'required|in:withdrawal,deposit,transfer,opening-balance,reconciliation',
            'description'                          => 'between:1,255',
            'date'                                 => ['required', new IsDateOrTime],
            'piggy_bank_id'                        => ['numeric', 'nullable', 'mustExist:piggy_banks,id', new BelongsUser],
            'piggy_bank_name'                      => ['between:1,255', 'nullable', new BelongsUser],
            'bill_id'                              => ['numeric', 'nullable', 'mustExist:bills,id', new BelongsUser],
            'bill_name'                            => ['between:1,255', 'nullable', new BelongsUser],
            'tags'                                 => 'between:1,255',

            // then, custom fields for journal
            'notes'                                => 'min:1,max:50000|nullable',

            // SEPA fields:
            'sepa_cc'                              => 'min:1,max:255|nullable',
            'sepa_ct_op'                           => 'min:1,max:255|nullable',
            'sepa_ct_id'                           => 'min:1,max:255|nullable',
            'sepa_db'                              => 'min:1,max:255|nullable',
            'sepa_country'                         => 'min:1,max:255|nullable',
            'sepa_ep'                              => 'min:1,max:255|nullable',
            'sepa_ci'                              => 'min:1,max:255|nullable',
            'sepa_batch_id'                        => 'min:1,max:255|nullable',

            // dates
            'interest_date'                        => 'date|nullable',
            'book_date'                            => 'date|nullable',
            'process_date'                         => 'date|nullable',
            'due_date'                             => 'date|nullable',
            'payment_date'                         => 'date|nullable',
            'invoice_date'                         => 'date|nullable',
            'internal_reference'                   => 'min:1,max:255|nullable',
            'bunq_payment_id'                      => 'min:1,max:255|nullable',
            'external_id'                          => 'min:1,max:255|nullable',

            // transaction rules (in array for splits):
            'transactions.*.amount'                => 'required|numeric|more:0',
            'transactions.*.description'           => 'nullable|between:1,255',
            'transactions.*.currency_id'           => 'numeric|exists:transaction_currencies,id',
            'transactions.*.currency_code'         => 'min:3|max:3|exists:transaction_currencies,code',
            'transactions.*.foreign_amount'        => 'numeric|more:0',
            'transactions.*.foreign_currency_id'   => 'numeric|exists:transaction_currencies,id',
            'transactions.*.foreign_currency_code' => 'min:3|max:3|exists:transaction_currencies,code',
            'transactions.*.budget_id'             => ['mustExist:budgets,id', new BelongsUser],
            'transactions.*.budget_name'           => ['between:1,255', 'nullable', new BelongsUser],
            'transactions.*.category_id'           => ['mustExist:categories,id', new BelongsUser],
            'transactions.*.category_name'         => 'between:1,255|nullable',
            'transactions.*.reconciled'            => [new IsBoolean],
            'transactions.*.source_id'             => ['numeric', 'nullable', new BelongsUser],
            'transactions.*.source_name'           => 'between:1,255|nullable',
            'transactions.*.destination_id'        => ['numeric', 'nullable', new BelongsUser],
            'transactions.*.destination_name'      => 'between:1,255|nullable',
        ];

        if ('PUT' === $this->method()) {
            unset($rules['type'], $rules['piggy_bank_id'], $rules['piggy_bank_name']);
        }

        return $rules;


    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator) {
                $this->validateOneTransaction($validator);
                $this->validateDescriptions($validator);
                $this->validateJournalDescription($validator);
                $this->validateSplitDescriptions($validator);
                $this->validateForeignCurrencyInformation($validator);
                $this->validateAccountInformation($validator);
                $this->validateSplitAccounts($validator);
            }
        );
    }

    /**
     * Get transaction data.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @return array
     */
    private function getTransactionData(): array
    {
        $return = [];
        foreach ($this->get('transactions') as $index => $transaction) {
            $return[] = [
                'amount'                => $transaction['amount'],
                'description'           => $transaction['description'] ?? null,
                'currency_id'           => isset($transaction['currency_id']) ? (int)$transaction['currency_id'] : null,
                'currency_code'         => $transaction['currency_code'] ?? null,
                'foreign_amount'        => $transaction['foreign_amount'] ?? null,
                'foreign_currency_id'   => isset($transaction['foreign_currency_id']) ? (int)$transaction['foreign_currency_id'] : null,
                'foreign_currency_code' => $transaction['foreign_currency_code'] ?? null,
                'budget_id'             => isset($transaction['budget_id']) ? (int)$transaction['budget_id'] : null,
                'budget_name'           => $transaction['budget_name'] ?? null,
                'category_id'           => isset($transaction['category_id']) ? (int)$transaction['category_id'] : null,
                'category_name'         => $transaction['category_name'] ?? null,
                'source_id'             => isset($transaction['source_id']) ? (int)$transaction['source_id'] : null,
                'source_name'           => isset($transaction['source_name']) ? (string)$transaction['source_name'] : null,
                'destination_id'        => isset($transaction['destination_id']) ? (int)$transaction['destination_id'] : null,
                'destination_name'      => isset($transaction['destination_name']) ? (string)$transaction['destination_name'] : null,
                'reconciled'            => $this->convertBoolean((string)($transaction['reconciled'] ?? 'false')),
                'identifier'            => $index,
            ];
        }

        return $return;
    }
}
