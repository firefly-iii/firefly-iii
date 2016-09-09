<?php
/**
 * JournalFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Requests;

use Auth;
use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionType;
use Input;

/**
 * Class JournalFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class JournalFormRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users
        return Auth::check();
    }

    /**
     * @return array
     */
    public function getJournalData()
    {
        $tags = $this->get('tags') ?? '';

        return [
            'what'                      => $this->get('what'),
            'description'               => $this->get('description'),
            'source_account_id'         => intval($this->get('source_account_id')),
            'source_account_name'       => $this->get('source_account_name') ?? '',
            'destination_account_id'    => intval($this->get('destination_account_id')),
            'destination_account_name'  => $this->get('destination_account_name') ?? '',
            'amount'                    => round($this->get('amount'), 2),
            'user'                      => Auth::user()->id,
            'amount_currency_id_amount' => intval($this->get('amount_currency_id_amount')),
            'date'                      => new Carbon($this->get('date')),
            'interest_date'             => $this->get('interest_date') ? new Carbon($this->get('interest_date')) : null,
            'book_date'                 => $this->get('book_date') ? new Carbon($this->get('book_date')) : null,
            'process_date'              => $this->get('process_date') ? new Carbon($this->get('process_date')) : null,
            'budget_id'                 => intval($this->get('budget_id')),
            'category'                  => $this->get('category') ?? '',
            'tags'                      => explode(',', $tags),
            'piggy_bank_id'             => $this->get('piggy_bank_id') ? intval($this->get('piggy_bank_id')) : 0,

            // new custom fields here:
            'due_date'                  => $this->get('due_date') ? new Carbon($this->get('due_date')) : null,
            'payment_date'              => $this->get('payment_date') ? new Carbon($this->get('payment_date')) : null,
            'internal_reference'        => $this->get('internal_reference'),
            'notes'                     => $this->get('notes'),

        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function rules()
    {
        $what  = Input::get('what');
        $rules = [
            'description'               => 'required|min:1,max:255',
            'what'                      => 'required|in:withdrawal,deposit,transfer',
            'amount'                    => 'numeric|required|min:0.01',
            'date'                      => 'required|date',
            'process_date'              => 'date',
            'book_date'                 => 'date',
            'interest_date'             => 'date',
            'category'                  => 'between:1,255',
            'amount_currency_id_amount' => 'required|exists:transaction_currencies,id',
            'piggy_bank_id'             => 'numeric',

            // new custom fields here:
            'due_date'                  => 'date',
            'payment_date'              => 'date',
            'internal_reference'        => 'min:1,max:255',
            'notes'                     => 'min:1,max:65536',
        ];

        switch ($what) {
            case strtolower(TransactionType::WITHDRAWAL):
                $rules['source_account_id']        = 'required|exists:accounts,id|belongsToUser:accounts';
                $rules['destination_account_name'] = 'between:1,255';
                if (intval(Input::get('budget_id')) != 0) {
                    $rules['budget_id'] = 'exists:budgets,id|belongsToUser:budgets';
                }
                break;
            case strtolower(TransactionType::DEPOSIT):
                $rules['source_account_name']    = 'between:1,255';
                $rules['destination_account_id'] = 'required|exists:accounts,id|belongsToUser:accounts';
                break;
            case strtolower(TransactionType::TRANSFER):
                $rules['source_account_id']      = 'required|exists:accounts,id|belongsToUser:accounts|different:destination_account_id';
                $rules['destination_account_id'] = 'required|exists:accounts,id|belongsToUser:accounts|different:source_account_id';

                break;
            default:
                throw new FireflyException('Cannot handle transaction type of type ' . e($what) . '.');
        }

        return $rules;
    }
}
