<?php
/**
 * SplitJournalFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Requests;

use Auth;
use Carbon\Carbon;


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
        return Auth::check();
    }

    /**
     * @return array
     */
    public function getSplitData(): array
    {
        $data = [
            'id'                               => $this->get('id') ?? 0,
            'journal_description'              => $this->get('journal_description'),
            'journal_currency_id'              => intval($this->get('journal_currency_id')),
            'journal_source_account_id'        => intval($this->get('journal_source_account_id')),
            'journal_source_account_name'      => $this->get('journal_source_account_name'),
            'journal_destination_account_id'   => intval($this->get('journal_destination_account_id')),
            'journal_destination_account_name' => $this->get('journal_source_destination_name'),
            'date'                             => new Carbon($this->get('date')),
            'what'                             => $this->get('what'),
            'interest_date'                    => $this->get('interest_date') ? new Carbon($this->get('interest_date')) : null,
            'book_date'                        => $this->get('book_date') ? new Carbon($this->get('book_date')) : null,
            'process_date'                     => $this->get('process_date') ? new Carbon($this->get('process_date')) : null,
            'transactions'                     => [],
        ];

        // description is leading because it is one of the mandatory fields.
        foreach ($this->get('description') as $index => $description) {
            $transaction            = [
                'description'              => $description,
                'amount'                   => round($this->get('amount')[$index], 2),
                'budget_id'                => $this->get('budget_id')[$index] ? intval($this->get('budget_id')[$index]) : 0,
                'category'                 => $this->get('category')[$index] ?? '',
                'source_account_id'        => intval($this->get('journal_source_account_id')),
                'source_account_name'      => $this->get('journal_source_account_name'),
                'piggy_bank_id'            => isset($this->get('piggy_bank_id')[$index])
                    ? intval($this->get('piggy_bank_id')[$index])
                    : 0,
                'destination_account_id'   => isset($this->get('destination_account_id')[$index])
                    ? intval($this->get('destination_account_id')[$index])
                    : intval($this->get('journal_destination_account_id')),
                'destination_account_name' => $this->get('destination_account_name')[$index] ?? '',
            ];
            $data['transactions'][] = $transaction;
        }

        return $data;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'what'                          => 'required|in:withdrawal,deposit,transfer',
            'journal_description'           => 'required|between:1,255',
            'id'                            => 'numeric|belongsToUser:transaction_journals,id',
            'journal_source_account_id'     => 'numeric|belongsToUser:accounts,id',
            'journal_source_account_name.*' => 'between:1,255',
            'journal_currency_id'           => 'required|exists:transaction_currencies,id',
            'date'                          => 'required|date',
            'interest_date'                 => 'date',
            'book_date'                     => 'date',
            'process_date'                  => 'date',

            'description.*'              => 'required|between:1,255',
            'destination_account_id.*'   => 'numeric|belongsToUser:accounts,id',
            'destination_account_name.*' => 'between:1,255',
            'amount.*'                   => 'required|numeric',
            'budget_id.*'                => 'belongsToUser:budgets,id',
            'category.*'                 => 'between:1,255',
            'piggy_bank_id.*'            => 'between:1,255',
        ];
    }
}