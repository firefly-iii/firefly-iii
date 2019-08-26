<?php
/**
 * RecurrenceStoreRequest.php
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

use Carbon\Carbon;
use FireflyIII\Rules\BelongsUser;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Validation\RecurrenceValidation;
use FireflyIII\Validation\TransactionValidation;
use Illuminate\Validation\Validator;

/**
 * Class RecurrenceStoreRequest
 */
class RecurrenceStoreRequest extends Request
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
        $active     = true;
        $applyRules = true;
        if (null !== $this->get('active')) {
            $active = $this->boolean('active');
        }
        if (null !== $this->get('apply_rules')) {
            $applyRules = $this->boolean('apply_rules');
        }
        $return = [
            'recurrence'   => [
                'type'         => $this->string('type'),
                'title'        => $this->string('title'),
                'description'  => $this->string('description'),
                'first_date'   => $this->date('first_date'),
                'repeat_until' => $this->date('repeat_until'),
                'repetitions'  => $this->integer('nr_of_repetitions'),
                'apply_rules'  => $applyRules,
                'active'       => $active,
            ],
            'transactions' => $this->getRecurrenceTransactionData(),
            'repetitions'  => $this->getRecurrenceRepetitionData(),
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
        $today = Carbon::now()->addDay();

        return [
            'type'                                 => 'required|in:withdrawal,transfer,deposit',
            'title'                                => 'required|between:1,255|uniqueObjectForUser:recurrences,title',
            'description'                          => 'between:1,65000',
            'first_date'                           => sprintf('required|date|after:%s', $today->format('Y-m-d')),
            'apply_rules'                          => [new IsBoolean],
            'active'                               => [new IsBoolean],
            'repeat_until'                         => sprintf('date|after:%s', $today->format('Y-m-d')),
            'nr_of_repetitions'                    => 'numeric|between:1,31',
            'repetitions.*.type'                   => 'required|in:daily,weekly,ndom,monthly,yearly',
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
            'transactions.*.piggy_bank_name'       => ['between:1,255', 'nullable', new BelongsUser],
            'transactions.*.piggy_bank_id'         => ['numeric', 'mustExist:piggy_banks,id', new BelongsUser],

            'transactions.*.tags' => 'between:1,64000',


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
                $this->validateOneRecurrenceTransaction($validator);
                $this->validateOneRepetition($validator);
                $this->validateRecurrenceRepetition($validator);
                $this->validateRepetitionMoment($validator);
                $this->validateForeignCurrencyInformation($validator);
                $this->validateAccountInformation($validator);
            }
        );
    }
}
