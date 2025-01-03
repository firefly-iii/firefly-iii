<?php

/**
 * RecurrenceFormRequest.php
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

use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\TransactionType;
use FireflyIII\Rules\IsValidPositiveAmount;
use FireflyIII\Rules\ValidRecurrenceRepetitionType;
use FireflyIII\Rules\ValidRecurrenceRepetitionValue;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\Validation\AccountValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Class RecurrenceFormRequest
 */
class RecurrenceFormRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Get the data required by the controller.
     *
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getAll(): array
    {
        $repetitionData                                = $this->parseRepetitionData();
        $return                                        = [
            'recurrence'   => [
                'type'              => $this->convertString('transaction_type'),
                'title'             => $this->convertString('title'),
                'description'       => $this->convertString('recurring_description'),
                'first_date'        => $this->getCarbonDate('first_date'),
                'repeat_until'      => $this->getCarbonDate('repeat_until'),
                'nr_of_repetitions' => $this->convertInteger('repetitions'),
                'apply_rules'       => $this->boolean('apply_rules'),
                'active'            => $this->boolean('active'),
                'repetition_end'    => $this->convertString('repetition_end'),
            ],
            'transactions' => [
                [
                    'currency_id'           => $this->convertInteger('transaction_currency_id'),
                    'currency_code'         => null,
                    'type'                  => $this->convertString('transaction_type'),
                    'description'           => $this->convertString('transaction_description'),
                    'amount'                => $this->convertString('amount'),
                    'foreign_amount'        => null,
                    'foreign_currency_id'   => null,
                    'foreign_currency_code' => null,
                    'budget_id'             => $this->convertInteger('budget_id'),
                    'budget_name'           => null,
                    'bill_id'               => $this->convertInteger('bill_id'),
                    'bill_name'             => null,
                    'category_id'           => null,
                    'category_name'         => $this->convertString('category'),
                    'tags'                  => '' !== $this->convertString('tags') ? explode(',', $this->convertString('tags')) : [],
                    'piggy_bank_id'         => $this->convertInteger('piggy_bank_id'),
                    'piggy_bank_name'       => null,
                ],
            ],
            'repetitions'  => [
                [
                    'type'    => $repetitionData['type'],
                    'moment'  => $repetitionData['moment'],
                    'skip'    => $this->convertInteger('skip'),
                    'weekend' => $this->convertInteger('weekend'),
                ],
            ],
        ];

        // fill in foreign currency data
        if (null !== $this->convertFloat('foreign_amount')) { // intentional float, used because it defaults to null.
            $return['transactions'][0]['foreign_amount']      = $this->convertString('foreign_amount');
            $return['transactions'][0]['foreign_currency_id'] = $this->convertInteger('foreign_currency_id');
        }
        // default values:
        $return['transactions'][0]['source_id']        = null;
        $return['transactions'][0]['source_name']      = null;
        $return['transactions'][0]['destination_id']   = null;
        $return['transactions'][0]['destination_name'] = null;
        $throwError                                    = true;
        $type                                          = $this->convertString('transaction_type');
        if ('withdrawal' === $type) {
            $throwError                                  = false;
            $return['transactions'][0]['source_id']      = $this->convertInteger('source_id');
            $return['transactions'][0]['destination_id'] = $this->convertInteger('withdrawal_destination_id');
        }
        if ('deposit' === $type) {
            $throwError                                  = false;
            $return['transactions'][0]['source_id']      = $this->convertInteger('deposit_source_id');
            $return['transactions'][0]['destination_id'] = $this->convertInteger('destination_id');
        }
        if ('transfer' === $type) {
            $throwError                                  = false;
            $return['transactions'][0]['source_id']      = $this->convertInteger('source_id');
            $return['transactions'][0]['destination_id'] = $this->convertInteger('destination_id');
        }
        if (true === $throwError) {
            throw new FireflyException(sprintf('Cannot handle transaction type "%s"', $this->convertString('transaction_type')));
        }

        // replace category name with a new category:
        $factory                                       = app(CategoryFactory::class);
        $factory->setUser(auth()->user());

        /**
         * @var int   $index
         * @var array $transaction
         */
        foreach ($return['transactions'] as $index => $transaction) {
            $categoryName = $transaction['category_name'] ?? null;
            if (null !== $categoryName) {
                $category = $factory->findOrCreate(null, $categoryName);
                if (null !== $category) {
                    $return['transactions'][$index]['category_id'] = $category->id;
                }
            }
        }

        return $return;
    }

    /**
     * Parses repetition data.
     */
    private function parseRepetitionData(): array
    {
        $value  = $this->convertString('repetition_type');
        $return = [
            'type'   => '',
            'moment' => '',
        ];

        if ('daily' === $value) {
            $return['type'] = $value;
        }
        // monthly,17
        // ndom,3,7
        if (in_array(substr($value, 0, 6), ['yearly', 'weekly'], true)) {
            $return['type']   = substr($value, 0, 6);
            $return['moment'] = substr($value, 7);
        }
        if (str_starts_with($value, 'monthly')) {
            $return['type']   = substr($value, 0, 7);
            $return['moment'] = substr($value, 8);
        }
        if (str_starts_with($value, 'ndom')) {
            $return['type']   = substr($value, 0, 4);
            $return['moment'] = substr($value, 5);
        }

        return $return;
    }

    /**
     * The rules for this request.
     */
    public function rules(): array
    {
        $today      = today(config('app.timezone'));
        $tomorrow   = today(config('app.timezone'))->addDay();
        $before     = today(config('app.timezone'))->addYears(25);
        $rules      = [
            // mandatory info for recurrence.
            'title'                   => 'required|min:1|max:255|uniqueObjectForUser:recurrences,title',
            'first_date'              => sprintf('required|date|before:%s|after:%s', $before->format('Y-m-d'), $today->format('Y-m-d')),
            'repetition_type'         => ['required', new ValidRecurrenceRepetitionValue(), new ValidRecurrenceRepetitionType(), 'min:1', 'max:32'],
            'skip'                    => 'required|numeric|integer|gte:0|lte:31',
            'notes'                   => 'min:1|max:32768|nullable',
            // optional for recurrence:
            'recurring_description'   => 'min:0|max:32768',
            'active'                  => 'numeric|min:0|max:1',
            'apply_rules'             => 'numeric|min:0|max:1',

            // mandatory for transaction:
            'transaction_description' => 'required|min:1|max:255',
            'transaction_type'        => 'required|in:withdrawal,deposit,transfer',
            'transaction_currency_id' => 'required|exists:transaction_currencies,id',
            'amount'                  => ['required', new IsValidPositiveAmount()],
            // mandatory account info:
            'source_id'               => 'numeric|belongsToUser:accounts,id|nullable',
            'source_name'             => 'min:1|max:255|nullable',
            'destination_id'          => 'numeric|belongsToUser:accounts,id|nullable',
            'destination_name'        => 'min:1|max:255|nullable',

            // foreign amount data:
            'foreign_amount'          => ['nullable', new IsValidPositiveAmount()],

            // optional fields:
            'budget_id'               => 'mustExist:budgets,id|belongsToUser:budgets,id|nullable',
            'bill_id'                 => 'mustExist:bills,id|belongsToUser:bills,id|nullable',
            'category'                => 'min:1|max:255|nullable',
            'tags'                    => 'min:1|max:255|nullable',
        ];
        if ($this->convertInteger('foreign_currency_id') > 0) {
            $rules['foreign_currency_id'] = 'exists:transaction_currencies,id';
        }

        // if ends after X repetitions, set another rule
        if ('times' === $this->convertString('repetition_end')) {
            $rules['repetitions'] = 'required|numeric|min:0|max:255';
        }
        // if foreign amount, currency must be  different.
        if (null !== $this->convertFloat('foreign_amount')) { // intentional float, used because it defaults to null.
            $rules['foreign_currency_id'] = 'exists:transaction_currencies,id|different:transaction_currency_id';
        }

        // if ends at date X, set another rule.
        if ('until_date' === $this->convertString('repetition_end')) {
            $rules['repeat_until'] = 'required|date|after:'.$tomorrow->format('Y-m-d');
        }

        // switch on type to expand rules for source and destination accounts:
        $type       = strtolower($this->convertString('transaction_type'));
        if (strtolower(TransactionTypeEnum::WITHDRAWAL->value) === $type) {
            $rules['source_id']        = 'required|exists:accounts,id|belongsToUser:accounts';
            $rules['destination_name'] = 'min:1|max:255|nullable';
        }
        if (strtolower(TransactionType::DEPOSIT) === $type) {
            $rules['source_name']    = 'min:1|max:255|nullable';
            $rules['destination_id'] = 'required|exists:accounts,id|belongsToUser:accounts';
        }
        if (strtolower(TransactionType::TRANSFER) === $type) {
            // this may not work:
            $rules['source_id']      = 'required|exists:accounts,id|belongsToUser:accounts|different:destination_id';
            $rules['destination_id'] = 'required|exists:accounts,id|belongsToUser:accounts|different:source_id';
        }

        // update some rules in case the user is editing a post:
        /** @var null|Recurrence $recurrence */
        $recurrence = $this->route()->parameter('recurrence');
        if ($recurrence instanceof Recurrence) {
            $rules['id']         = 'required|numeric|exists:recurrences,id';
            $rules['title']      = 'required|min:1|max:255|uniqueObjectForUser:recurrences,title,'.$recurrence->id;
            $rules['first_date'] = 'required|date';
        }

        return $rules;
    }

    /**
     * Configure the validator instance with special rules for after the basic validation rules.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                // validate all account info
                $this->validateAccountInformation($validator);
            }
        );
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', __CLASS__), $validator->errors()->toArray());
        }
    }

    /**
     * Validates the given account information. Switches on given transaction type.
     *
     * @throws FireflyException
     */
    public function validateAccountInformation(Validator $validator): void
    {
        app('log')->debug('Now in validateAccountInformation (RecurrenceFormRequest)()');

        /** @var AccountValidator $accountValidator */
        $accountValidator = app(AccountValidator::class);
        $data             = $validator->getData();
        $transactionType  = $data['transaction_type'] ?? 'invalid';

        $accountValidator->setTransactionType($transactionType);

        // default values:
        $sourceId         = null;
        $destinationId    = null;

        // TODO typeOverrule: the account validator may have another opinion the transaction type.
        // TODO either use 'withdrawal' or the strtolower() variant, not both.
        $type             = $this->convertString('transaction_type');
        $throwError       = true;
        if ('withdrawal' === $type) {
            $throwError    = false;
            $sourceId      = (int) $data['source_id'];
            $destinationId = (int) $data['withdrawal_destination_id'];
        }
        if ('deposit' === $type) {
            $throwError    = false;
            $sourceId      = (int) $data['deposit_source_id'];
            $destinationId = (int) ($data['destination_id'] ?? 0);
        }
        if ('transfer' === $type) {
            $throwError    = false;
            $sourceId      = (int) $data['source_id'];
            $destinationId = (int) ($data['destination_id'] ?? 0);
        }
        if (true === $throwError) {
            throw new FireflyException(sprintf('Cannot handle transaction type "%s"', $this->convertString('transaction_type')));
        }

        // validate source account.
        $validSource      = $accountValidator->validateSource(['id' => $sourceId]);

        // do something with result:
        if (false === $validSource) {
            $message = (string) trans('validation.generic_invalid_source');
            $validator->errors()->add('source_id', $message);
            $validator->errors()->add('deposit_source_id', $message);

            return;
        }

        // validate destination account
        $validDestination = $accountValidator->validateDestination(['id' => $destinationId]);
        // do something with result:
        if (false === $validDestination) {
            $message = (string) trans('validation.generic_invalid_destination');
            $validator->errors()->add('destination_id', $message);
            $validator->errors()->add('withdrawal_destination_id', $message);
        }
    }
}
