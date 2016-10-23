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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionType;

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
     * Returns and validates the data required to store a new journal. Can handle both single transaction journals and split journals.
     *
     * @return array
     */
    public function getJournalData()
    {
        $data = [
            'what'                     => $this->get('what'), // type. can be 'deposit', 'withdrawal' or 'transfer'
            'date'                     => new Carbon($this->get('date')),
            'tags'                     => explode(',', $this->getFieldOrEmptyString('tags')),
            'currency_id'              => intval($this->get('amount_currency_id_amount')),

            // all custom fields:
            'interest_date'            => $this->getDateOrNull('interest_date'),
            'book_date'                => $this->getDateOrNull('book_date'),
            'process_date'             => $this->getDateOrNull('process_date'),
            'due_date'                 => $this->getDateOrNull('due_date'),
            'payment_date'             => $this->getDateOrNull('payment_date'),
            'invoice_date'             => $this->getDateOrNull('invoice_date'),
            'internal_reference'       => trim(strval($this->get('internal_reference'))),
            'notes'                    => trim(strval($this->get('notes'))),

            // transaction / journal data:
            'description'              => $this->getFieldOrEmptyString('description'),
            'amount'                   => round($this->get('amount'), 2),
            'budget_id'                => intval($this->get('budget_id')),
            'category'                 => $this->getFieldOrEmptyString('category'),
            'source_account_id'        => intval($this->get('source_account_id')),
            'source_account_name'      => $this->getFieldOrEmptyString('source_account_name'),
            'destination_account_id'   => $this->getFieldOrEmptyString('destination_account_id'),
            'destination_account_name' => $this->getFieldOrEmptyString('destination_account_name'),
            'piggy_bank_id'            => intval($this->get('piggy_bank_id')),

        ];

        return $data;
    }

    /**
     * @return array
     */
    public function rules()
    {
        $what  = $this->get('what');
        $rules = [
            'what'                     => 'required|in:withdrawal,deposit,transfer',
            'date'                     => 'required|date',

            // then, custom fields:
            'interest_date'            => 'date',
            'book_date'                => 'date',
            'process_date'             => 'date',
            'due_date'                 => 'date',
            'payment_date'             => 'date',
            'invoice_date'             => 'date',
            'internal_reference'       => 'min:1,max:255',
            'notes'                    => 'min:1,max:50000',
            // and then transaction rules:
            'description'              => 'required|between:1,255',
            'amount'                   => 'numeric|required|min:0.01',
            'budget_id'                => 'mustExist:budgets,id|belongsToUser:budgets,id',
            'category'                 => 'between:1,255',
            'source_account_id'        => 'numeric|belongsToUser:accounts,id',
            'source_account_name'      => 'between:1,255',
            'destination_account_id'   => 'numeric|belongsToUser:accounts,id',
            'destination_account_name' => 'between:1,255',
            'piggy_bank_id'            => 'between:1,255',
        ];

        // some rules get an upgrade depending on the type of data:
        $rules = $this->enhanceRules($what, $rules);

        return $rules;
    }

    /**
     * Inspired by https://www.youtube.com/watch?v=WwnI0RS6J5A
     *
     * @param string $what
     * @param array  $rules
     *
     * @return array
     * @throws FireflyException
     */
    private function enhanceRules(string $what, array $rules): array
    {
        switch ($what) {
            case strtolower(TransactionType::WITHDRAWAL):
                $rules['source_account_id']        = 'required|exists:accounts,id|belongsToUser:accounts';
                $rules['destination_account_name'] = 'between:1,255';
                break;
            case strtolower(TransactionType::DEPOSIT):
                $rules['source_account_name']    = 'between:1,255';
                $rules['destination_account_id'] = 'required|exists:accounts,id|belongsToUser:accounts';
                break;
            case strtolower(TransactionType::TRANSFER):
                // this may not work:
                $rules['source_account_id']      = 'required|exists:accounts,id|belongsToUser:accounts|different:destination_account_id';
                $rules['destination_account_id'] = 'required|exists:accounts,id|belongsToUser:accounts|different:source_account_id';

                break;
            default:
                throw new FireflyException('Cannot handle transaction type of type ' . e($what) . ' . ');
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
