<?php
/**
 * JournalFormRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Requests;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionType;
use Illuminate\Validation\Validator;
use Log;

/**
 * Class JournalFormRequest.
 */
class JournalFormRequest extends Request
{
    /**
     * Verify the request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * Returns and validates the data required to store a new journal. Can handle both single transaction journals and split journals.
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getJournalData(): array
    {
        $currencyId = $this->integer('amount_currency_id_amount');
        $data       = [
            'type'               => $this->get('what'), // type. can be 'deposit', 'withdrawal' or 'transfer'
            'date'               => $this->date('date'),
            'tags'               => explode(',', $this->string('tags')),
            'user'               => auth()->user()->id,

            // all custom fields:
            'interest_date'      => $this->date('interest_date'),
            'book_date'          => $this->date('book_date'),
            'process_date'       => $this->date('process_date'),
            'due_date'           => $this->date('due_date'),
            'payment_date'       => $this->date('payment_date'),
            'invoice_date'       => $this->date('invoice_date'),
            'internal_reference' => $this->string('internal_reference'),
            'notes'              => $this->string('notes'),

            // journal data:
            'description'        => $this->string('description'),
            'piggy_bank_id'      => $this->integer('piggy_bank_id'),
            'piggy_bank_name'    => null,
            'bill_id'            => null,
            'bill_name'          => null,

            // transaction data:
            'transactions'       => [
                [
                    'currency_id'           => null,
                    'currency_code'         => null,
                    'description'           => null,
                    'amount'                => $this->string('amount'),
                    'budget_id'             => $this->integer('budget_id'),
                    'budget_name'           => null,
                    'category_id'           => null,
                    'category_name'         => $this->string('category'),
                    'source_id'             => $this->integer('source_id'),
                    'source_name'           => $this->string('source_name'),
                    'destination_id'        => $this->integer('destination_id'),
                    'destination_name'      => $this->string('destination_name'),
                    'foreign_currency_id'   => null,
                    'foreign_currency_code' => null,
                    'foreign_amount'        => null,
                    'reconciled'            => false,
                    'identifier'            => 0,
                ],
            ],
        ];
        switch (strtolower($data['type'])) {
            case 'withdrawal':
                $sourceCurrency                            = $this->integer('source_account_currency');
                $data['transactions'][0]['currency_id']    = $sourceCurrency;
                $data['transactions'][0]['destination_id'] = null; // clear destination ID (transfer)
                if ($sourceCurrency !== $currencyId) {
                    // user has selected a foreign currency.
                    $data['transactions'][0]['foreign_currency_id'] = $currencyId;
                    $data['transactions'][0]['foreign_amount']      = $this->string('amount');
                    $data['transactions'][0]['amount']              = $this->string('native_amount');
                }

                break;
            case 'deposit':
                $destinationCurrency                    = $this->integer('destination_account_currency');
                $data['transactions'][0]['currency_id'] = $destinationCurrency;
                $data['transactions'][0]['source_id']   = null; // clear destination ID (transfer)
                if ($destinationCurrency !== $currencyId) {
                    // user has selected a foreign currency.
                    $data['transactions'][0]['foreign_currency_id'] = $currencyId;
                    $data['transactions'][0]['foreign_amount']      = $this->string('amount');
                    $data['transactions'][0]['amount']              = $this->string('native_amount');
                }
                break;
            case 'transfer':
                // by default just assume source currency
                $sourceCurrency                         = $this->integer('source_account_currency');
                $destinationCurrency                    = $this->integer('destination_account_currency');
                $data['transactions'][0]['currency_id'] = $sourceCurrency;
                if ($sourceCurrency !== $destinationCurrency) {
                    // user has selected a foreign currency.
                    $data['transactions'][0]['foreign_currency_id'] = $destinationCurrency;
                    $data['transactions'][0]['foreign_amount']      = $this->string('destination_amount');
                    $data['transactions'][0]['amount']              = $this->string('source_amount');
                }
                break;

        }

        return $data;
    }

    /**
     * Rules for this request.
     *
     * @return array
     * @throws FireflyException
     */
    public function rules(): array
    {
        $what  = $this->get('what');
        $rules = [
            'what'                      => 'required|in:withdrawal,deposit,transfer',
            'date'                      => 'required|date',
            'amount_currency_id_amount' => 'exists:transaction_currencies,id|required',
            // then, custom fields:
            'interest_date'             => 'date|nullable',
            'book_date'                 => 'date|nullable',
            'process_date'              => 'date|nullable',
            'due_date'                  => 'date|nullable',
            'payment_date'              => 'date|nullable',
            'invoice_date'              => 'date|nullable',
            'internal_reference'        => 'min:1|max:255|nullable',
            'notes'                     => 'min:1|max:50000|nullable',
            // and then transaction rules:
            'description'               => 'required|between:1,255',
            'amount'                    => 'numeric|required|more:0|less:10000000',//
            'budget_id'                 => 'mustExist:budgets,id|belongsToUser:budgets,id|nullable',
            'category'                  => 'between:1,255|nullable',
            'source_id'                 => 'numeric|belongsToUser:accounts,id|nullable',
            'source_name'               => 'between:1,255|nullable',
            'destination_id'            => 'numeric|belongsToUser:accounts,id|nullable',
            'destination_name'          => 'between:1,255|nullable',
            'piggy_bank_id'             => 'numeric|nullable',

            // foreign currency amounts
            'native_amount'             => 'numeric|more:0|nullable',
            'source_amount'             => 'numeric|more:0|nullable',
            'destination_amount'        => 'numeric|more:0|nullable',
        ];

        // some rules get an upgrade depending on the type of data:
        $rules = $this->enhanceRules($what, $rules);

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
                $this->validNativeAmount($validator);
            }
        );
    }

    /**
     * Inspired by https://www.youtube.com/watch?v=WwnI0RS6J5A.
     *
     * @param string $what
     * @param array  $rules
     *
     * @return array
     *
     * @throws FireflyException
     */
    private function enhanceRules(string $what, array $rules): array
    {
        switch ($what) {
            case strtolower(TransactionType::WITHDRAWAL):
                $rules['source_id']        = 'required|exists:accounts,id|belongsToUser:accounts';
                $rules['destination_name'] = 'between:1,255|nullable';
                break;
            case strtolower(TransactionType::DEPOSIT):
                $rules['source_name']    = 'between:1,255|nullable';
                $rules['destination_id'] = 'required|exists:accounts,id|belongsToUser:accounts';
                break;
            case strtolower(TransactionType::TRANSFER):
                // this may not work:
                $rules['source_id']      = 'required|exists:accounts,id|belongsToUser:accounts|different:destination_id';
                $rules['destination_id'] = 'required|exists:accounts,id|belongsToUser:accounts|different:source_id';

                break;
            default:
                throw new FireflyException(sprintf('Cannot handle transaction type of type "%s"', $what)); // @codeCoverageIgnore
        }

        return $rules;
    }

    /**
     * Check if amounts are valid.
     *
     * @param Validator $validator
     */
    private function validNativeAmount(Validator $validator): void
    {
        $data = $validator->getData();
        $type = $data['what'] ?? 'invalid';
        Log::debug(sprintf('Type is %s', $type));
        if ('withdrawal' === $type) {
            $this->validateWithdrawal($validator);
        }

        // same thing for deposits:
        if ('deposit' === $type) {
            $this->validateDeposit($validator);
        }

        // and for transfers
        if ('transfer' === $type) {
            $this->validateTransfer($validator);
        }
    }

    /**
     * Check if deposit amount is valid.
     *
     * @param Validator $validator
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function validateDeposit(Validator $validator): void
    {
        $data             = $validator->getData();
        $selectedCurrency = (int)($data['amount_currency_id_amount'] ?? 0);
        $accountCurrency  = (int)($data['destination_account_currency'] ?? 0);
        $nativeAmount     = (string)($data['native_amount'] ?? '');
        if ($selectedCurrency !== $accountCurrency && '' === $nativeAmount && 0 !== $selectedCurrency && 0 !== $accountCurrency) {
            $validator->errors()->add('native_amount', (string)trans('validation.numeric_native'));

            return;
        }
    }

    /**
     * Check if transfer amount is valid.
     *
     * @param Validator $validator
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function validateTransfer(Validator $validator): void
    {
        $data                = $validator->getData();
        $sourceCurrency      = (int)($data['source_account_currency'] ?? 0);
        $destinationCurrency = (int)($data['destination_account_currency'] ?? 0);
        $sourceAmount        = (string)($data['source_amount'] ?? '');
        $destinationAmount   = (string)($data['destination_amount'] ?? '');

        Log::debug(sprintf('Source currency is %d, destination currency is %d', $sourceCurrency, $destinationCurrency));

        if ($sourceCurrency !== $destinationCurrency && '' === $sourceAmount && 0 !== $sourceCurrency && 0 !== $destinationCurrency) {
            $validator->errors()->add('source_amount', (string)trans('validation.numeric_source'));
        }

        if ($sourceCurrency !== $destinationCurrency && '' === $destinationAmount && 0 !== $sourceCurrency && 0 !== $destinationCurrency) {
            $validator->errors()->add('destination_amount', (string)trans('validation.numeric_destination'));
            $validator->errors()->add('destination_amount', (string)trans('validation.numeric', ['attribute' => 'destination_amount']));
        }

    }

    /**
     * Check if withdrawal amount is valid.
     *
     * @param Validator $validator
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function validateWithdrawal(Validator $validator): void
    {
        $data             = $validator->getData();
        $selectedCurrency = (int)($data['amount_currency_id_amount'] ?? 0);
        $accountCurrency  = (int)($data['source_account_currency'] ?? 0);
        Log::debug(sprintf('Selected currency is %d, account currency is %d', $selectedCurrency, $accountCurrency));
        $nativeAmount = (string)($data['native_amount'] ?? '');
        if ($selectedCurrency !== $accountCurrency && '' === $nativeAmount
            && 0 !== $selectedCurrency
            && 0 !== $accountCurrency
        ) {
            Log::debug('ADD validation error on native_amount');
            $validator->errors()->add('native_amount', (string)trans('validation.numeric_native'));

            return;
        }
    }
}
