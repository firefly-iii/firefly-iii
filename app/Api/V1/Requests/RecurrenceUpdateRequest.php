<?php
/**
 * RecurrenceUpdateRequest.php
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

use FireflyIII\Models\Recurrence;
use FireflyIII\Rules\BelongsUser;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Validation\RecurrenceValidation;
use FireflyIII\Validation\TransactionValidation;
use Illuminate\Validation\Validator;

/**
 * Class RecurrenceUpdateRequest
 */
class RecurrenceUpdateRequest extends Request
{
    use RecurrenceValidation, TransactionValidation;

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
     * Get all data from the request.
     *
     * @return array
     */
    public function getAll(): array
    {
        $active     = null;
        $applyRules = null;
        if (null !== $this->get('active')) {
            $active = $this->boolean('active');
        }
        if (null !== $this->get('apply_rules')) {
            $applyRules = $this->boolean('apply_rules');
        }
        $return = [
            'recurrence'   => [
                'type'              => $this->nullableString('type'),
                'title'             => $this->nullableString('title'),
                'description'       => $this->nullableString('description'),
                'first_date'        => $this->date('first_date'),
                'repeat_until'      => $this->date('repeat_until'),
                'nr_of_repetitions' => $this->nullableInteger('nr_of_repetitions'),
                'apply_rules'       => $applyRules,
                'active'            => $active,
            ],
            'transactions' => $this->getTransactionData(),
            'repetitions'  => $this->getRepetitionData(),
        ];

        return $return;
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        /** @var Recurrence $recurrence */
        $recurrence = $this->route()->parameter('recurrence');
        $first      = clone $recurrence->first_date;
        $first->subDay();

        return [
            'type'                                 => 'in:withdrawal,transfer,deposit',
            'title'                                => sprintf('between:1,255|uniqueObjectForUser:recurrences,title,%d', $recurrence->id),
            'description'                          => 'between:1,65000',
            'first_date'                           => sprintf('date|after:%s', $first->format('Y-m-d')),
            'apply_rules'                          => [new IsBoolean],
            'active'                               => [new IsBoolean],
            'repeat_until'                         => sprintf('date|after:%s', $first->format('Y-m-d')),
            'nr_of_repetitions'                    => 'numeric|between:1,31',
            'repetitions.*.type'                   => 'in:daily,weekly,ndom,monthly,yearly',
            'repetitions.*.moment'                 => 'between:0,10',
            'repetitions.*.skip'                   => 'required|numeric|between:0,31',
            'repetitions.*.weekend'                => 'required|numeric|min:1|max:4',
            'transactions.*.description'           => 'required|between:1,255',
            'transactions.*.amount'                => 'required|numeric|more:0',
            'transactions.*.foreign_amount'        => 'numeric|more:0',
            'transactions.*.currency_id'           => 'numeric|exists:transaction_currencies,id',
            'transactions.*.currency_code'         => 'min:3|max:3|exists:transaction_currencies,code',
            'transactions.*.foreign_currency_id'   => 'numeric|exists:transaction_currencies,id',
            'transactions.*.foreign_currency_code' => 'min:3|max:3|exists:transaction_currencies,code',
            'transactions.*.source_id'             => ['numeric', 'nullable', new BelongsUser],
            'transactions.*.source_name'           => 'between:1,255|nullable',
            'transactions.*.destination_id'        => ['numeric', 'nullable', new BelongsUser],
            'transactions.*.destination_name'      => 'between:1,255|nullable',

            // new and updated fields:
            'transactions.*.budget_id'             => ['mustExist:budgets,id', new BelongsUser],
            'transactions.*.budget_name'           => ['between:1,255', 'nullable', new BelongsUser],
            'transactions.*.category_id'           => ['mustExist:categories,id', new BelongsUser],
            'transactions.*.category_name'         => 'between:1,255|nullable',
            'transactions.*.piggy_bank_id'         => ['numeric', 'mustExist:piggy_banks,id', new BelongsUser],
            'transactions.*.piggy_bank_name'       => ['between:1,255', 'nullable', new BelongsUser],
            'transactions.*.tags'                  => 'between:1,64000',

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
        $validator->after(
            function (Validator $validator) {
                $this->validateOneRecurrenceTransactionUpdate($validator);
                $this->validateOneRepetitionUpdate($validator);
                $this->validateRecurrenceRepetition($validator);
                $this->validateRepetitionMoment($validator);
                $this->validateForeignCurrencyInformation($validator);
                $this->validateAccountInformation($validator);
            }
        );
    }

    /**
     * Returns the repetition data as it is found in the submitted data.
     *
     * @return array|null
     */
    private function getRepetitionData(): ?array
    {
        $return = [];
        // repetition data:
        /** @var array $repetitions */
        $repetitions = $this->get('repetitions');
        if (null === $repetitions) {
            return null;
        }
        /** @var array $repetition */
        foreach ($repetitions as $repetition) {
            $return[] = [
                'type'    => $repetition['type'],
                'moment'  => $repetition['moment'],
                'skip'    => (int)$repetition['skip'],
                'weekend' => (int)$repetition['weekend'],
            ];
        }

        return $return;
    }

    /**
     * Returns the transaction data as it is found in the submitted data. It's a complex method according to code
     * standards but it just has a lot of ??-statements because of the fields that may or may not exist.
     *
     * @return array|null
     */
    private function getTransactionData(): ?array
    {
        $return = [];
        // transaction data:
        /** @var array $transactions */
        $transactions = $this->get('transactions');
        if (null === $transactions) {
            return null;
        }
        /** @var array $transaction */
        foreach ($transactions as $transaction) {
            $return[] = [
                'amount'                => $transaction['amount'],
                'currency_id'           => isset($transaction['currency_id']) ? (int)$transaction['currency_id'] : null,
                'currency_code'         => $transaction['currency_code'] ?? null,
                'foreign_amount'        => $transaction['foreign_amount'] ?? null,
                'foreign_currency_id'   => isset($transaction['foreign_currency_id']) ? (int)$transaction['foreign_currency_id'] : null,
                'foreign_currency_code' => $transaction['foreign_currency_code'] ?? null,
                'source_id'             => isset($transaction['source_id']) ? (int)$transaction['source_id'] : null,
                'source_name'           => isset($transaction['source_name']) ? (string)$transaction['source_name'] : null,
                'destination_id'        => isset($transaction['destination_id']) ? (int)$transaction['destination_id'] : null,
                'destination_name'      => isset($transaction['destination_name']) ? (string)$transaction['destination_name'] : null,
                'description'           => $transaction['description'],
                'type'                  => $this->string('type'),

                // new and updated fields:
                'piggy_bank_id'         => isset($transaction['piggy_bank_id']) ? (int)$transaction['piggy_bank_id'] : null,
                'piggy_bank_name'       => $transaction['piggy_bank_name'] ?? null,
                'tags'                  => $transaction['tags'],
                'budget_id'             => isset($transaction['budget_id']) ? (int)$transaction['budget_id'] : null,
                'budget_name'           => $transaction['budget_name'] ?? null,
                'category_id'           => isset($transaction['category_id']) ? (int)$transaction['category_id'] : null,
                'category_name'         => $transaction['category_name'] ?? null,
            ];
        }

        return $return;
    }
}
