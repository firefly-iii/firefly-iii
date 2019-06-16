<?php

/**
 * Request.php
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

use Carbon\Carbon;
use FireflyIII\Http\Requests\Request as FireflyIIIRequest;
use FireflyIII\Rules\BelongsUser;
use FireflyIII\Rules\IsBoolean;

/**
 * Class Request.
 *
 * Technically speaking this class does not have to be extended like this but who knows what the future brings.
 *
 */
class Request extends FireflyIIIRequest
{
    /**
     * @return array
     */
    public function getAllAccountData(): array
    {
        $active          = true;
        $includeNetWorth = true;
        if (null !== $this->get('active')) {
            $active = $this->boolean('active');
        }
        if (null !== $this->get('include_net_worth')) {
            $includeNetWorth = $this->boolean('include_net_worth');
        }

        $data = [
            'name'                    => $this->string('name'),
            'active'                  => $active,
            'include_net_worth'       => $includeNetWorth,
            'account_type'            => $this->string('type'),
            'account_type_id'         => null,
            'currency_id'             => $this->integer('currency_id'),
            'currency_code'           => $this->string('currency_code'),
            'virtual_balance'         => $this->string('virtual_balance'),
            'iban'                    => $this->string('iban'),
            'BIC'                     => $this->string('bic'),
            'account_number'          => $this->string('account_number'),
            'account_role'            => $this->string('account_role'),
            'opening_balance'         => $this->string('opening_balance'),
            'opening_balance_date'    => $this->date('opening_balance_date'),
            'cc_type'                 => $this->string('credit_card_type'),
            'cc_Monthly_payment_date' => $this->string('monthly_payment_date'),
            'notes'                   => $this->string('notes'),
            'interest'                => $this->string('interest'),
            'interest_period'         => $this->string('interest_period'),
        ];

        if ('liability' === $data['account_type']) {
            $data['opening_balance']      = bcmul($this->string('liability_amount'), '-1');
            $data['opening_balance_date'] = $this->date('liability_start_date');
            $data['account_type']         = $this->string('liability_type');
            $data['account_type_id']      = null;
        }

        return $data;
    }

    /**
     * Get all data from the request.
     *
     * @return array
     */
    public function getAllRecurrenceData(): array
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
            'meta'         => [
                'piggy_bank_id'   => $this->integer('piggy_bank_id'),
                'piggy_bank_name' => $this->string('piggy_bank_name'),
                'tags'            => explode(',', $this->string('tags')),
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
    protected function rulesRecurrence(): array
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
            'tags'                                 => 'between:1,64000',
            'piggy_bank_id'                        => 'numeric',
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
            'transactions.*.budget_id'             => ['mustExist:budgets,id', new BelongsUser],
            'transactions.*.category_name'         => 'between:1,255|nullable',
            'transactions.*.source_id'             => ['numeric', 'nullable', new BelongsUser],
            'transactions.*.source_name'           => 'between:1,255|nullable',
            'transactions.*.destination_id'        => ['numeric', 'nullable', new BelongsUser],
            'transactions.*.destination_name'      => 'between:1,255|nullable',


        ];
    }

    /**
     * Returns the repetition data as it is found in the submitted data.
     *
     * @return array
     */
    protected function getRecurrenceRepetitionData(): array
    {
        $return = [];
        // repetition data:
        /** @var array $repetitions */
        $repetitions = $this->get('repetitions');
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
     * @return array
     */
    protected function getRecurrenceTransactionData(): array
    {
        $return = [];
        // transaction data:
        /** @var array $transactions */
        $transactions = $this->get('transactions');
        /** @var array $transaction */
        foreach ($transactions as $transaction) {
            $return[] = [
                'amount'                => $transaction['amount'],
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
                'description'           => $transaction['description'],
                'type'                  => $this->string('type'),
            ];
        }

        return $return;
    }

}
