<?php

/**
 * RecurrenceUpdateRequest.php
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

namespace FireflyIII\Api\V1\Requests\Models\Recurrence;

use FireflyIII\Models\Recurrence;
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
 * Class UpdateRequest
 */
class UpdateRequest extends FormRequest
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
        // this is the way:
        $fields                 = [
            'title'             => ['title', 'convertString'],
            'description'       => ['description', 'convertString'],
            'first_date'        => ['first_date', 'convertDateTime'],
            'repeat_until'      => ['repeat_until', 'convertDateTime'],
            'nr_of_repetitions' => ['nr_of_repetitions', 'convertInteger'],
            'apply_rules'       => ['apply_rules', 'boolean'],
            'active'            => ['active', 'boolean'],
            'notes'             => ['notes', 'convertString'],
        ];
        $reps                   = $this->getRepetitionData();
        $transactions           = $this->getTransactionData();
        $return                 = [
            'recurrence' => $this->getAllData($fields),
        ];
        if (null !== $reps) {
            $return['repetitions'] = $reps;
        }
        $return['transactions'] = $transactions;

        return $return;
    }

    /**
     * Returns the repetition data as it is found in the submitted data.
     */
    private function getRepetitionData(): ?array
    {
        $return      = [];

        // repetition data:
        /** @var null|array $repetitions */
        $repetitions = $this->get('repetitions');
        if (null === $repetitions) {
            return null;
        }

        /** @var array $repetition */
        foreach ($repetitions as $repetition) {
            $current  = [];
            if (array_key_exists('type', $repetition)) {
                $current['type'] = $repetition['type'];
            }

            if (array_key_exists('moment', $repetition)) {
                $current['moment'] = (string) $repetition['moment'];
            }

            if (array_key_exists('skip', $repetition)) {
                $current['skip'] = (int) $repetition['skip'];
            }

            if (array_key_exists('weekend', $repetition)) {
                $current['weekend'] = (int) $repetition['weekend'];
            }
            $return[] = $current;
        }
        if (0 === count($return)) {
            return null;
        }

        return $return;
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
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->route()->parameter('recurrence');

        return [
            'title'                                => sprintf('min:1|max:255|uniqueObjectForUser:recurrences,title,%d', $recurrence->id),
            'description'                          => 'min:1|max:32768',
            'first_date'                           => 'date|after:1900-01-01|before:2099-12-31',
            'apply_rules'                          => [new IsBoolean()],
            'active'                               => [new IsBoolean()],
            'repeat_until'                         => 'nullable|date',
            'nr_of_repetitions'                    => 'nullable|numeric|min:1|max:31',

            'repetitions.*.type'                   => 'in:daily,weekly,ndom,monthly,yearly',
            'repetitions.*.moment'                 => 'min:0|max:10|numeric',
            'repetitions.*.skip'                   => 'nullable|numeric|min:0|max:31',
            'repetitions.*.weekend'                => 'nullable|numeric|min:1|max:4',

            'transactions.*.description'           => ['min:1', 'max:255'],
            'transactions.*.amount'                => [new IsValidPositiveAmount()],
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
                // $this->validateOneRecurrenceTransaction($validator);
                // $this->validateOneRepetitionUpdate($validator);

                /** @var Recurrence $recurrence */
                $recurrence = $this->route()->parameter('recurrence');
                $this->validateTransactionId($recurrence, $validator);
                $this->validateRecurrenceRepetition($validator);
                $this->validateRepetitionMoment($validator);
                $this->validateForeignCurrencyInformation($validator);
                $this->valUpdateAccountInfo($validator);
            }
        );
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
