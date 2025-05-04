<?php

/*
 * RecurrenceStoreRequest.php
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

namespace FireflyIII\Api\V1\Requests\Models\Recurrence;

use FireflyIII\Rules\BelongsUser;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Rules\IsValidPositiveAmount;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\Support\Request\GetRecurrenceData;
use FireflyIII\Validation\CurrencyValidation;
use FireflyIII\Validation\RecurrenceValidation;
use FireflyIII\Validation\TransactionValidation;
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
    use CurrencyValidation;
    use GetRecurrenceData;
    use RecurrenceValidation;
    use TransactionValidation;

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        $fields     = [
            'type'              => ['type', 'convertString'],
            'title'             => ['title', 'convertString'],
            'description'       => ['description', 'convertString'],
            'first_date'        => ['first_date', 'convertDateTime'],
            'repeat_until'      => ['repeat_until', 'convertDateTime'],
            'nr_of_repetitions' => ['nr_of_repetitions', 'convertInteger'],
            'apply_rules'       => ['apply_rules', 'boolean'],
            'active'            => ['active', 'boolean'],
            'notes'             => ['notes', 'stringWithNewlines'],
        ];
        $recurrence = $this->getAllData($fields);

        return [
            'recurrence'   => $recurrence,
            'transactions' => $this->getTransactionData(),
            'repetitions'  => $this->getRepetitionData(),
        ];
    }

    /**
     * Returns the transaction data as it is found in the submitted data. It's a complex method according to code
     * standards but it just has a lot of ??-statements because of the fields that may or may not exist.
     */
    private function getTransactionData(): array
    {
        $return       = [];

        // transaction data:
        /** @var null|array $transactions */
        $transactions = $this->get('transactions');
        if (null === $transactions) {
            return [];
        }

        /** @var array $transaction */
        foreach ($transactions as $transaction) {
            $return[] = $this->getSingleTransactionData($transaction);
        }

        return $return;
    }

    /**
     * Returns the repetition data as it is found in the submitted data.
     */
    private function getRepetitionData(): array
    {
        $return      = [];

        // repetition data:
        /** @var null|array $repetitions */
        $repetitions = $this->get('repetitions');
        if (null === $repetitions) {
            return [];
        }

        /** @var array $repetition */
        foreach ($repetitions as $repetition) {
            $current  = [];
            if (array_key_exists('type', $repetition)) {
                $current['type'] = $repetition['type'];
            }
            if (array_key_exists('moment', $repetition)) {
                $current['moment'] = $repetition['moment'];
            }
            if (array_key_exists('skip', $repetition)) {
                $current['skip'] = (int) $repetition['skip'];
            }
            if (array_key_exists('weekend', $repetition)) {
                $current['weekend'] = (int) $repetition['weekend'];
            }

            $return[] = $current;
        }

        return $return;
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        return [
            'type'                                 => 'required|in:withdrawal,transfer,deposit',
            'title'                                => 'required|min:1|max:255|uniqueObjectForUser:recurrences,title',
            'description'                          => 'min:1|max:32768',
            'first_date'                           => 'required|date',
            'apply_rules'                          => [new IsBoolean()],
            'active'                               => [new IsBoolean()],
            'repeat_until'                         => 'nullable|date',
            'nr_of_repetitions'                    => 'nullable|numeric|min:1|max:31',

            'repetitions.*.type'                   => 'required|in:daily,weekly,ndom,monthly,yearly',
            'repetitions.*.moment'                 => 'min:0|max:10',
            'repetitions.*.skip'                   => 'nullable|numeric|min:0|max:31',
            'repetitions.*.weekend'                => 'numeric|min:1|max:4',

            'transactions.*.description'           => 'required|min:1|max:255',
            'transactions.*.amount'                => ['required', new IsValidPositiveAmount()],
            'transactions.*.foreign_amount'        => ['nullable', new IsValidPositiveAmount()],
            'transactions.*.currency_id'           => 'nullable|numeric|exists:transaction_currencies,id',
            'transactions.*.currency_code'         => 'nullable|min:3|max:51|exists:transaction_currencies,code',
            'transactions.*.foreign_currency_id'   => 'nullable|numeric|exists:transaction_currencies,id',
            'transactions.*.foreign_currency_code' => 'nullable|min:3|max:51|exists:transaction_currencies,code',
            'transactions.*.source_id'             => ['numeric', 'nullable', new BelongsUser()],
            'transactions.*.source_name'           => 'min:1|max:255|nullable',
            'transactions.*.destination_id'        => ['numeric', 'nullable', new BelongsUser()],
            'transactions.*.destination_name'      => 'min:1|max:255|nullable',

            // new and updated fields:
            'transactions.*.budget_id'             => ['nullable', 'mustExist:budgets,id', new BelongsUser()],
            'transactions.*.budget_name'           => ['min:1', 'max:255', 'nullable', new BelongsUser()],
            'transactions.*.category_id'           => ['nullable', 'mustExist:categories,id', new BelongsUser()],
            'transactions.*.category_name'         => 'min:1|max:255|nullable',
            'transactions.*.piggy_bank_id'         => ['nullable', 'numeric', 'mustExist:piggy_banks,id', new BelongsUser()],
            'transactions.*.piggy_bank_name'       => ['min:1', 'max:255', 'nullable', new BelongsUser()],
            'transactions.*.tags'                  => 'nullable|min:1|max:255',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                $this->validateRecurringConfig($validator);
                $this->validateOneRecurrenceTransaction($validator);
                $this->validateOneRepetition($validator);
                $this->validateRecurrenceRepetition($validator);
                $this->validateRepetitionMoment($validator);
                $this->validateForeignCurrencyInformation($validator);
                $this->validateAccountInformation($validator);
            }
        );
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
