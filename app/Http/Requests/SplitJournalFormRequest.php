<?php
/**
 * SplitJournalFormRequest.php
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

use Illuminate\Validation\Validator;

/**
 * Class SplitJournalFormRequest.
 */
class SplitJournalFormRequest extends Request
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
     * Get all info for the controller.
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getAll(): array
    {
        $data = [
            'description'     => $this->string('journal_description'),
            'type'            => $this->string('what'),
            'date'            => $this->date('date'),
            'tags'            => explode(',', $this->string('tags')),
            'bill_id'         => null,
            'bill_name'       => null,
            'piggy_bank_id'   => null,
            'piggy_bank_name' => null,
            'notes'           => $this->string('notes'),
            'transactions'    => [],
        ];
        // switch type to get correct source / destination info:
        $sourceId        = null;
        $sourceName      = null;
        $destinationId   = null;
        $destinationName = null;

        foreach ($this->get('transactions') as $index => $transaction) {
            switch ($data['type']) {
                case 'withdrawal':
                    $sourceId        = $this->integer('journal_source_id');
                    $destinationName = $transaction['destination_name'] ?? '';
                    break;
                case 'deposit':
                    $sourceName    = $transaction['source_name'] ?? '';
                    $destinationId = $this->integer('journal_destination_id');
                    break;
                case 'transfer':
                    $sourceId      = $this->integer('journal_source_id');
                    $destinationId = $this->integer('journal_destination_id');
                    break;
            }
            $foreignAmount          = $transaction['foreign_amount'] ?? null;
            $foreignCurrencyId      = (int)($transaction['foreign_currency_id'] ?? 0.0);
            $set                    = [
                'source_id'             => $sourceId,
                'source_name'           => $sourceName,
                'destination_id'        => $destinationId,
                'destination_name'      => $destinationName,
                'foreign_amount'        => $foreignAmount,
                'foreign_currency_id'   => $foreignCurrencyId,
                'foreign_currency_code' => null,
                'reconciled'            => false,
                'identifier'            => $index,
                'currency_id'           => (int)$transaction['transaction_currency_id'],
                'currency_code'         => null,
                'description'           => $transaction['transaction_description'] ?? '',
                'amount'                => $transaction['amount'] ?? '',
                'budget_id'             => (int)($transaction['budget_id'] ?? 0.0),
                'budget_name'           => null,
                'category_id'           => null,
                'category_name'         => $transaction['category_name'] ?? '',
            ];
            $data['transactions'][] = $set;
        }

        return $data;
    }

    /**
     * Rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'what'                                   => 'required|in:withdrawal,deposit,transfer',
            'journal_description'                    => 'required|between:1,255',
            'id'                                     => 'numeric|belongsToUser:transaction_journals,id',
            'journal_source_id'                      => 'numeric|belongsToUser:accounts,id',
            'journal_source_name.*'                  => 'between:1,255',
            'journal_currency_id'                    => 'required|exists:transaction_currencies,id',
            'date'                                   => 'required|date',
            'interest_date'                          => 'date|nullable',
            'book_date'                              => 'date|nullable',
            'process_date'                           => 'date|nullable',
            'transactions.*.transaction_description' => 'required|between:1,255',
            'transactions.*.destination_id'          => 'numeric|belongsToUser:accounts,id',
            'transactions.*.destination_name'        => 'between:1,255|nullable',
            'transactions.*.amount'                  => 'required|numeric',
            'transactions.*.budget_id'               => 'belongsToUser:budgets,id',
            'transactions.*.category_name'           => 'between:1,255|nullable',
            'transactions.*.piggy_bank_id'           => 'numeric|nullable',
        ];
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
                $this->sameAccounts($validator);
            }
        );
    }

    /**
     * Verify that source and destination are not the same.
     *
     * @param Validator $validator
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function sameAccounts(Validator $validator): void
    {
        $data         = $this->getAll();
        $transactions = $data['transactions'] ?? [];
        /** @var array $array */
        foreach ($transactions as $array) {
            if (null !== $array['destination_id'] && null !== $array['source_id'] && $array['destination_id'] === $array['source_id']) {
                $validator->errors()->add('journal_source_id', (string)trans('validation.source_equals_destination'));
                $validator->errors()->add('journal_destination_id', (string)trans('validation.source_equals_destination'));
            }
        }

    }

}
