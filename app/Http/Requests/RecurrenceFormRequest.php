<?php
/**
 * RecurrenceFormRequest.php
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

namespace FireflyIII\Http\Requests;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\TransactionType;
use FireflyIII\Rules\ValidRecurrenceRepetitionType;
use FireflyIII\Rules\ValidRecurrenceRepetitionValue;

/**
 * Class RecurrenceFormRequest
 */
class RecurrenceFormRequest extends Request
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * @return array
     * @throws FireflyException
     */
    public function getAll(): array
    {
        $repetitionData = $this->parseRepetitionData();
        $return         = [
            'recurrence'   => [
                'type'           => $this->string('transaction_type'),
                'title'          => $this->string('title'),
                'description'    => $this->string('recurring_description'),
                'first_date'     => $this->date('first_date'),
                'repeat_until'   => $this->date('repeat_until'),
                'repetitions'    => $this->integer('repetitions'),
                'apply_rules'    => $this->boolean('apply_rules'),
                'active'         => $this->boolean('active'),
                'repetition_end' => $this->string('repetition_end'),
            ],
            'transactions' => [
                [
                    'transaction_currency_id' => $this->integer('transaction_currency_id'),
                    'type'                    => $this->string('transaction_type'),
                    'description'             => $this->string('transaction_description'),
                    'amount'                  => $this->string('amount'),
                    'foreign_amount'          => null,
                    'foreign_currency_id'     => null,
                    'budget_id'               => $this->integer('budget_id'),
                    'category_name'           => $this->string('category'),

                ],
            ],
            'meta'         => [
                // tags and piggy bank ID.
                'tags'          => '' !== $this->string('tags') ? explode(',', $this->string('tags')): [],
                'piggy_bank_id' => $this->integer('piggy_bank_id'),
            ],
            'repetitions'  => [
                [
                    'type'   => $repetitionData['type'],
                    'moment' => $repetitionData['moment'],
                    'skip'   => $this->integer('skip'),
                ],
            ],

        ];

        // fill in foreign currency data
        if (null !== $this->float('foreign_amount')) {
            $return['transactions'][0]['foreign_amount']      = $this->string('foreign_amount');
            $return['transactions'][0]['foreign_currency_id'] = $this->integer('foreign_currency_id');
        }

        // fill in source and destination account data
        switch ($this->string('transaction_type')) {
            default:
                throw new FireflyException(sprintf('Cannot handle transaction type "%s"', $this->string('transaction_type')));
            case 'withdrawal':
                $return['transactions'][0]['source_account_id']        = $this->integer('source_account_id');
                $return['transactions'][0]['destination_account_name'] = $this->string('destination_account_name');
                break;
            case 'deposit':
                $return['transactions'][0]['source_account_name']    = $this->string('source_account_name');
                $return['transactions'][0]['destination_account_id'] = $this->integer('destination_account_id');
                break;
            case 'transfer':
                $return['transactions'][0]['source_account_id']      = $this->integer('source_account_id');
                $return['transactions'][0]['destination_account_id'] = $this->integer('destination_account_id');
                break;
        }

        return $return;
    }

    /**
     * @return array
     * @throws FireflyException
     */
    public function rules(): array
    {
        $today    = new Carbon;
        $tomorrow = clone $today;
        $tomorrow->addDay();
        $rules = [
            // mandatory info for recurrence.
            'title'                    => 'required|between:1,255|uniqueObjectForUser:recurrences,title',
            'first_date'               => 'required|date|after:' . $today->format('Y-m-d'),
            'repetition_type'          => ['required', new ValidRecurrenceRepetitionValue, new ValidRecurrenceRepetitionType, 'between:1,20'],
            'skip'                     => 'required|numeric|between:0,31',

            // optional for recurrence:
            'recurring_description'    => 'between:0,65000',
            'active'                   => 'numeric|between:0,1',
            'apply_rules'              => 'numeric|between:0,1',

            // mandatory for transaction:
            'transaction_description'  => 'required|between:1,255',
            'transaction_type'         => 'required|in:withdrawal,deposit,transfer',
            'transaction_currency_id'  => 'required|exists:transaction_currencies,id',
            'amount'                   => 'numeric|required|more:0',
            // mandatory account info:
            'source_account_id'        => 'numeric|belongsToUser:accounts,id|nullable',
            'source_account_name'      => 'between:1,255|nullable',
            'destination_account_id'   => 'numeric|belongsToUser:accounts,id|nullable',
            'destination_account_name' => 'between:1,255|nullable',

            // foreign amount data:
            'foreign_amount'           => 'nullable|more:0',

            // optional fields:
            'budget_id'                => 'mustExist:budgets,id|belongsToUser:budgets,id|nullable',
            'category'                 => 'between:1,255|nullable',
            'tags'                     => 'between:1,255|nullable',
        ];
        if ($this->integer('foreign_currency_id') > 0) {
            $rules['foreign_currency_id'] = 'exists:transaction_currencies,id';
        }

        // if ends after X repetitions, set another rule
        if ($this->string('repetition_end') === 'times') {
            $rules['repetitions'] = 'required|numeric|between:0,254';
        }
        // if foreign amount, currency must be  different.
        if ($this->float('foreign_amount') !== 0.0) {
            $rules['foreign_currency_id'] = 'exists:transaction_currencies,id|different:transaction_currency_id';
        }

        // if ends at date X, set another rule.
        if ($this->string('repetition_end') === 'until_date') {
            $rules['repeat_until'] = 'required|date|after:' . $tomorrow->format('Y-m-d');
        }

        // switchc on type to expand rules for source and destination accounts:
        switch ($this->string('transaction_type')) {
            case strtolower(TransactionType::WITHDRAWAL):
                $rules['source_account_id']        = 'required|exists:accounts,id|belongsToUser:accounts';
                $rules['destination_account_name'] = 'between:1,255|nullable';
                break;
            case strtolower(TransactionType::DEPOSIT):
                $rules['source_account_name']    = 'between:1,255|nullable';
                $rules['destination_account_id'] = 'required|exists:accounts,id|belongsToUser:accounts';
                break;
            case strtolower(TransactionType::TRANSFER):
                // this may not work:
                $rules['source_account_id']      = 'required|exists:accounts,id|belongsToUser:accounts|different:destination_account_id';
                $rules['destination_account_id'] = 'required|exists:accounts,id|belongsToUser:accounts|different:source_account_id';

                break;
            default:
                throw new FireflyException(sprintf('Cannot handle transaction type of type "%s"', $this->string('transaction_type'))); // @codeCoverageIgnore
        }

        // update some rules in case the user is editing a post:
        /** @var Recurrence $recurrence */
        $recurrence = $this->route()->parameter('recurrence');
        if ($recurrence instanceof Recurrence) {
            $rules['id']         = 'required|numeric|exists:recurrences,id';
            $rules['title']      = 'required|between:1,255|uniqueObjectForUser:recurrences,title,' . $recurrence->id;
            $rules['first_date'] = 'required|date';
        }


        return $rules;
    }

    /**
     * @return array
     */
    private function parseRepetitionData(): array
    {
        $value  = $this->string('repetition_type');
        $return = [
            'type'   => '',
            'moment' => '',
        ];

        if ($value === 'daily') {
            $return['type'] = $value;
        }
        //monthly,17
        //ndom,3,7
        if (\in_array(substr($value, 0, 6), ['yearly', 'weekly'])) {
            $return['type']   = substr($value, 0, 6);
            $return['moment'] = substr($value, 7);
        }
        if (0 === strpos($value, 'monthly')) {
            $return['type']   = substr($value, 0, 7);
            $return['moment'] = substr($value, 8);
        }
        if (0 === strpos($value, 'ndom')) {
            $return['type']   = substr($value, 0, 4);
            $return['moment'] = substr($value, 5);
        }

        return $return;


    }
}