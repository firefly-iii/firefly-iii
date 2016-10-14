<?php
/**
 * JournalFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Requests;

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
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getJournalData()
    {
        $tags = $this->getFieldOrEmptyString('tags');

        return [
            'what'                      => $this->get('what'),
            'description'               => trim($this->get('description')),
            'source_account_id'         => intval($this->get('source_account_id')),
            'source_account_name'       => trim($this->getFieldOrEmptyString('source_account_name')),
            'destination_account_id'    => intval($this->get('destination_account_id')),
            'destination_account_name'  => trim($this->getFieldOrEmptyString('destination_account_name')),
            'amount'                    => round($this->get('amount'), 2),
            'user'                      => auth()->user()->id,
            'amount_currency_id_amount' => intval($this->get('amount_currency_id_amount')),
            'date'                      => new Carbon($this->get('date')),
            'interest_date'             => $this->getDateOrNull('interest_date'),
            'book_date'                 => $this->getDateOrNull('book_date'),
            'process_date'              => $this->getDateOrNull('process_date'),
            'budget_id'                 => intval($this->get('budget_id')),
            'category'                  => trim($this->getFieldOrEmptyString('category')),
            'tags'                      => explode(',', $tags),
            'piggy_bank_id'             => intval($this->get('piggy_bank_id')),

            // new custom fields here:
            'due_date'                  => $this->getDateOrNull('due_date'),
            'payment_date'              => $this->getDateOrNull('payment_date'),
            'invoice_date'              => $this->getDateOrNull('invoice_date'),
            'internal_reference'        => trim(strval($this->get('internal_reference'))),
            'notes'                     => trim(strval($this->get('notes'))),

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

    /**
     * @param string $field
     *
     * @return Carbon|null
     */
    private function getDateOrNull(string $field)
    {
        return $this->get($field) ? new Carbon($this->get($field)) : null;
    }

    /**
     * @param string $field
     *
     * @return string
     */
    private function getFieldOrEmptyString(string $field): string
    {
        return $this->get($field) ?? '';
    }
}
