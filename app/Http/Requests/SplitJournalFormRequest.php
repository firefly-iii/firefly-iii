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

use Steam;

/**
 * Class SplitJournalFormRequest.
 */
class SplitJournalFormRequest extends Request
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
     */
    public function getAll(): array
    {
        $data = [
            'description'     => $this->string('description'),
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
                    $sourceId        = $this->integer('journal_source_account_id');
                    $destinationName = $transaction['destination_account_name'];
                    break;
                case 'deposit':
                    $sourceName    = $transaction['source_account_name'];
                    $destinationId = $this->integer('journal_destination_account_id');
                    break;
                case 'transfer':
                    $sourceId      = $this->integer('journal_source_account_id');
                    $destinationId = $this->integer('journal_destination_account_id');
                    break;
            }
            $foreignAmount          = $transaction['foreign_amount'] ?? null;
            $foreignCurrency        = isset($transaction['foreign_currency_id']) ? intval($transaction['foreign_currency_id']) : null;
            $set                    = [
                'source_id'             => $sourceId,
                'source_name'           => $sourceName,
                'destination_id'        => $destinationId,
                'destination_name'      => $destinationName,
                'foreign_amount'        => $foreignAmount,
                'foreign_currency_id'   => null,
                'foreign_currency_code' => null,
                'reconciled'            => false,
                'identifier'            => $index,
                'currency_id'           => $this->integer('journal_currency_id'),
                'currency_code'         => null,
                'description'           => $transaction['description'],
                'amount'                => $transaction['amount'],
                'budget_id'             => intval($transaction['budget_id'] ?? 0),
                'budget_name'           => null,
                'category_id'           => null,
                'category_name'         => $transaction['category'],
            ];
            $data['transactions'][] = $set;
        }

        return $data;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'what'                                    => 'required|in:withdrawal,deposit,transfer',
            'journal_description'                     => 'required|between:1,255',
            'id'                                      => 'numeric|belongsToUser:transaction_journals,id',
            'journal_source_account_id'               => 'numeric|belongsToUser:accounts,id',
            'journal_source_account_name.*'           => 'between:1,255',
            'journal_currency_id'                     => 'required|exists:transaction_currencies,id',
            'date'                                    => 'required|date',
            'interest_date'                           => 'date|nullable',
            'book_date'                               => 'date|nullable',
            'process_date'                            => 'date|nullable',
            'transactions.*.description'              => 'required|between:1,255',
            'transactions.*.destination_account_id'   => 'numeric|belongsToUser:accounts,id',
            'transactions.*.destination_account_name' => 'between:1,255|nullable',
            'transactions.*.amount'                   => 'required|numeric',
            'transactions.*.budget_id'                => 'belongsToUser:budgets,id',
            'transactions.*.category'                 => 'between:1,255|nullable',
            'transactions.*.piggy_bank_id'            => 'between:1,255|nullable',
        ];
    }

    /**
     * @return array
     */
    private function getTransactionData(): array
    {
        $descriptions    = $this->getArray('description', 'string');
        $categories      = $this->getArray('category', 'string');
        $amounts         = $this->getArray('amount', 'float');
        $budgets         = $this->getArray('amount', 'integer');
        $srcAccountIds   = $this->getArray('source_account_id', 'integer');
        $srcAccountNames = $this->getArray('source_account_name', 'string');
        $dstAccountIds   = $this->getArray('destination_account_id', 'integer');
        $dstAccountNames = $this->getArray('destination_account_name', 'string');
        $piggyBankIds    = $this->getArray('piggy_bank_id', 'integer');

        $return = [];
        // description is leading because it is one of the mandatory fields.
        foreach ($descriptions as $index => $description) {
            $category    = $categories[$index] ?? '';
            $transaction = [
                'description'              => $description,
                'amount'                   => Steam::positive($amounts[$index]),
                'budget_id'                => $budgets[$index] ?? 0,
                'category'                 => $category,
                'source_account_id'        => $srcAccountIds[$index] ?? $this->get('journal_source_account_id'),
                'source_account_name'      => $srcAccountNames[$index] ?? '',
                'piggy_bank_id'            => $piggyBankIds[$index] ?? 0,
                'destination_account_id'   => $dstAccountIds[$index] ?? $this->get('journal_destination_account_id'),
                'destination_account_name' => $dstAccountNames[$index] ?? '',
            ];
            $return[]    = $transaction;
        }

        return $return;
    }
}
