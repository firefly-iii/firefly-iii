<?php

/*
 * UpdateRequest.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V2\Request\Model\Transaction;

use FireflyIII\Api\V1\Requests\Models\AvailableBudget\Request;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Rules\BelongsUser;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Rules\IsDateOrTime;
use FireflyIII\Rules\IsValidPositiveAmount;
use FireflyIII\Rules\IsValidZeroOrMoreAmount;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\Validation\GroupValidation;
use FireflyIII\Validation\TransactionValidation;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Class UpdateRequest
 *
 * TODO it's the same as the v1
 */
class UpdateRequest extends Request
{
    use ChecksLogin;
    use ConvertsDataTypes;
    use GroupValidation;
    use TransactionValidation;

    private array $arrayFields;
    private array $booleanFields;
    private array $dateFields;
    private array $floatFields;
    private array $integerFields;
    private array $stringFields;
    private array $textareaFields;

    /**
     * Get all data. Is pretty complex because of all the ??-statements.
     *
     * @throws FireflyException
     */
    public function getAll(): array
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        $this->integerFields  = ['order', 'currency_id', 'foreign_currency_id', 'transaction_journal_id', 'source_id', 'destination_id', 'budget_id', 'category_id', 'bill_id', 'recurrence_id'];
        $this->dateFields     = ['date', 'interest_date', 'book_date', 'process_date', 'due_date', 'payment_date', 'invoice_date'];
        $this->textareaFields = ['notes'];
        // not really floats, for validation.
        $this->floatFields    = ['amount', 'foreign_amount'];
        $this->stringFields   = ['type', 'currency_code', 'foreign_currency_code', 'description', 'source_name', 'source_iban', 'source_number', 'source_bic', 'destination_name', 'destination_iban', 'destination_number', 'destination_bic', 'budget_name', 'category_name', 'bill_name', 'internal_reference', 'external_id', 'bunq_payment_id', 'sepa_cc', 'sepa_ct_op', 'sepa_ct_id', 'sepa_db', 'sepa_country', 'sepa_ep', 'sepa_ci', 'sepa_batch_id', 'external_url'];
        $this->booleanFields  = ['reconciled'];
        $this->arrayFields    = ['tags'];
        $data                 = [];
        if ($this->has('transactions')) {
            $data['transactions'] = $this->getTransactionData();
        }
        if ($this->has('apply_rules')) {
            $data['apply_rules'] = $this->boolean('apply_rules', true);
        }
        if ($this->has('fire_webhooks')) {
            $data['fire_webhooks'] = $this->boolean('fire_webhooks', true);
        }
        if ($this->has('group_title')) {
            $data['group_title'] = $this->convertString('group_title');
        }

        return $data;
    }

    /**
     * Get transaction data.
     *
     * @throws FireflyException
     */
    private function getTransactionData(): array
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        $return       = [];

        /** @var null|array $transactions */
        $transactions = $this->get('transactions');

        if (!is_countable($transactions)) {
            return $return;
        }

        /** @var null|array $transaction */
        foreach ($transactions as $transaction) {
            if (!is_array($transaction)) {
                throw new FireflyException('Invalid data submitted: transaction is not array.');
            }
            // default response is to update nothing in the transaction:
            $current  = [];
            $current  = $this->getIntegerData($current, $transaction);
            $current  = $this->getStringData($current, $transaction);
            $current  = $this->getNlStringData($current, $transaction);
            $current  = $this->getDateData($current, $transaction);
            $current  = $this->getBooleanData($current, $transaction);
            $current  = $this->getArrayData($current, $transaction);
            $current  = $this->getFloatData($current, $transaction);
            $return[] = $current;
        }

        return $return;
    }

    /**
     * For each field, add it to the array if a reference is present in the request:
     *
     * @param array<string, string> $current
     * @param array<string, mixed>  $transaction
     */
    private function getIntegerData(array $current, array $transaction): array
    {
        foreach ($this->integerFields as $fieldName) {
            if (array_key_exists($fieldName, $transaction)) {
                $current[$fieldName] = $this->integerFromValue((string) $transaction[$fieldName]);
            }
        }

        return $current;
    }

    /**
     * @param array<string, string> $current
     * @param array<string, mixed>  $transaction
     */
    private function getStringData(array $current, array $transaction): array
    {
        foreach ($this->stringFields as $fieldName) {
            if (array_key_exists($fieldName, $transaction)) {
                $current[$fieldName] = $this->clearString((string) $transaction[$fieldName]);
            }
        }

        return $current;
    }

    /**
     * @param array<string, string> $current
     * @param array<string, mixed>  $transaction
     */
    private function getNlStringData(array $current, array $transaction): array
    {
        foreach ($this->textareaFields as $fieldName) {
            if (array_key_exists($fieldName, $transaction)) {
                $current[$fieldName] = $this->clearStringKeepNewlines((string) $transaction[$fieldName]); // keep newlines
            }
        }

        return $current;
    }

    /**
     * @param array<string, string> $current
     * @param array<string, mixed>  $transaction
     */
    private function getDateData(array $current, array $transaction): array
    {
        foreach ($this->dateFields as $fieldName) {
            app('log')->debug(sprintf('Now at date field %s', $fieldName));
            if (array_key_exists($fieldName, $transaction)) {
                app('log')->debug(sprintf('New value: "%s"', (string) $transaction[$fieldName]));
                $current[$fieldName] = $this->dateFromValue((string) $transaction[$fieldName]);
            }
        }

        return $current;
    }

    /**
     * @param array<string, string> $current
     * @param array<string, mixed>  $transaction
     */
    private function getBooleanData(array $current, array $transaction): array
    {
        foreach ($this->booleanFields as $fieldName) {
            if (array_key_exists($fieldName, $transaction)) {
                $current[$fieldName] = $this->convertBoolean((string) $transaction[$fieldName]);
            }
        }

        return $current;
    }

    /**
     * @param array<string, string> $current
     * @param array<string, mixed>  $transaction
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
     * @param array<string, string> $current
     * @param array<string, mixed>  $transaction
     */
    private function getFloatData(array $current, array $transaction): array
    {
        foreach ($this->floatFields as $fieldName) {
            if (array_key_exists($fieldName, $transaction)) {
                $value = $transaction[$fieldName];
                if (is_float($value)) {
                    $current[$fieldName] = sprintf('%.12f', $value);
                }
                if (!is_float($value)) {
                    $current[$fieldName] = (string) $value;
                }
            }
        }

        return $current;
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        $validProtocols = config('firefly.valid_url_protocols');

        return [
            // basic fields for group:
            'group_title'                           => 'min:1|max:1000|nullable',
            'apply_rules'                           => [new IsBoolean()],

            // transaction rules (in array for splits):
            'transactions.*.type'                   => 'in:withdrawal,deposit,transfer,opening-balance,reconciliation',
            'transactions.*.date'                   => [new IsDateOrTime()],
            'transactions.*.order'                  => 'numeric|min:0',

            // group id:
            'transactions.*.transaction_journal_id' => ['nullable', 'numeric', new BelongsUser()],

            // currency info
            'transactions.*.currency_id'            => 'numeric|exists:transaction_currencies,id|nullable',
            'transactions.*.currency_code'          => 'min:3|max:51|exists:transaction_currencies,code|nullable',
            'transactions.*.foreign_currency_id'    => 'nullable|numeric|exists:transaction_currencies,id',
            'transactions.*.foreign_currency_code'  => 'nullable|min:3|max:51|exists:transaction_currencies,code',

            // amount
            'transactions.*.amount'                 => ['nullable', new IsValidPositiveAmount()],
            'transactions.*.foreign_amount'         => ['nullable', new IsValidZeroOrMoreAmount()],

            // description
            'transactions.*.description'            => 'nullable|min:1|max:1000',

            // source of transaction
            'transactions.*.source_id'              => ['numeric', 'nullable', new BelongsUser()],
            'transactions.*.source_name'            => 'min:1|max:255|nullable',

            // destination of transaction
            'transactions.*.destination_id'         => ['numeric', 'nullable', new BelongsUser()],
            'transactions.*.destination_name'       => 'min:1|max:255|nullable',

            // budget, category, bill and piggy
            'transactions.*.budget_id'              => ['mustExist:budgets,id', new BelongsUser(), 'nullable'],
            'transactions.*.budget_name'            => ['min:1', 'max:255', 'nullable', new BelongsUser()],
            'transactions.*.category_id'            => ['mustExist:categories,id', new BelongsUser(), 'nullable'],
            'transactions.*.category_name'          => 'min:1|max:255|nullable',
            'transactions.*.bill_id'                => ['numeric', 'nullable', 'mustExist:bills,id', new BelongsUser()],
            'transactions.*.bill_name'              => ['min:1', 'max:255', 'nullable', new BelongsUser()],

            // other interesting fields
            'transactions.*.reconciled'             => [new IsBoolean()],
            'transactions.*.notes'                  => 'min:1|max:32768|nullable',
            'transactions.*.tags'                   => 'min:0|max:255|nullable',
            'transactions.*.tags.*'                 => 'min:0|max:255',

            // meta info fields
            'transactions.*.internal_reference'     => 'min:1|max:255|nullable',
            'transactions.*.external_id'            => 'min:1|max:255|nullable',
            'transactions.*.recurrence_id'          => 'min:1|max:255|nullable',
            'transactions.*.bunq_payment_id'        => 'min:1|max:255|nullable',
            'transactions.*.external_url'           => sprintf('min:1|max:255|nullable|url:%s', $validProtocols),

            // SEPA fields:
            'transactions.*.sepa_cc'                => 'min:1|max:255|nullable',
            'transactions.*.sepa_ct_op'             => 'min:1|max:255|nullable',
            'transactions.*.sepa_ct_id'             => 'min:1|max:255|nullable',
            'transactions.*.sepa_db'                => 'min:1|max:255|nullable',
            'transactions.*.sepa_country'           => 'min:1|max:255|nullable',
            'transactions.*.sepa_ep'                => 'min:1|max:255|nullable',
            'transactions.*.sepa_ci'                => 'min:1|max:255|nullable',
            'transactions.*.sepa_batch_id'          => 'min:1|max:255|nullable',

            // dates
            'transactions.*.interest_date'          => 'date|nullable',
            'transactions.*.book_date'              => 'date|nullable',
            'transactions.*.process_date'           => 'date|nullable',
            'transactions.*.due_date'               => 'date|nullable',
            'transactions.*.payment_date'           => 'date|nullable',
            'transactions.*.invoice_date'           => 'date|nullable',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        app('log')->debug('Now in withValidator');

        /** @var TransactionGroup $transactionGroup */
        $transactionGroup = $this->route()->parameter('userGroupTransaction');
        $validator->after(
            function (Validator $validator) use ($transactionGroup): void {
                // if more than one, verify that there are journal ID's present.
                $this->validateJournalIds($validator, $transactionGroup);

                // all transaction types must be equal:
                $this->validateTransactionTypesForUpdate($validator);

                // user wants to update a reconciled transaction.
                // source, destination, amount + foreign_amount cannot be changed
                // and must be omitted from the request.
                $this->preventUpdateReconciled($validator, $transactionGroup);

                // validate source/destination is equal, depending on the transaction journal type.
                $this->validateEqualAccountsForUpdate($validator, $transactionGroup);

                // see method:
                // $this->preventNoAccountInfo($validator, );

                // validate that the currency fits the source and/or destination account.
                // validate all account info
                $this->validateAccountInformationUpdate($validator, $transactionGroup);
            }
        );
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', __CLASS__), $validator->errors()->toArray());
        }
    }
}
