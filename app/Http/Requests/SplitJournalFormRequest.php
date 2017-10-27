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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

use Steam;

/**
 * Class SplitJournalFormRequest
 *
 * @package FireflyIII\Http\Requests
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
    public function getSplitData(): array
    {
        $data = [
            'id'                               => $this->integer('id'),
            'journal_description'              => $this->string('journal_description'),
            'journal_currency_id'              => $this->integer('journal_currency_id'),
            'journal_source_account_id'        => $this->integer('journal_source_account_id'),
            'journal_source_account_name'      => $this->string('journal_source_account_name'),
            'journal_destination_account_id'   => $this->integer('journal_destination_account_id'),
            'journal_destination_account_name' => $this->string('journal_source_destination_name'),
            'date'                             => $this->date('date'),
            'what'                             => $this->string('what'),
            'interest_date'                    => $this->date('interest_date'),
            'book_date'                        => $this->date('book_date'),
            'process_date'                     => $this->date('process_date'),
            'transactions'                     => $this->getTransactionData(),
        ];

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
