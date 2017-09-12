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

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

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
            'date'                     => $this->date('date'),
            'tags'                     => explode(',', $this->string('tags')),
            'currency_id'              => $this->integer('amount_currency_id_amount'),

            // all custom fields:
            'interest_date'            => $this->date('interest_date'),
            'book_date'                => $this->date('book_date'),
            'process_date'             => $this->date('process_date'),
            'due_date'                 => $this->date('due_date'),
            'payment_date'             => $this->date('payment_date'),
            'invoice_date'             => $this->date('invoice_date'),
            'internal_reference'       => $this->string('internal_reference'),
            'notes'                    => $this->string('notes'),

            // transaction / journal data:
            'description'              => $this->string('description'),
            'amount'                   => $this->float('amount'),
            'budget_id'                => $this->integer('budget_id'),
            'category'                 => $this->string('category'),
            'source_account_id'        => $this->integer('source_account_id'),
            'source_account_name'      => $this->string('source_account_name'),
            'destination_account_id'   => $this->string('destination_account_id'),
            'destination_account_name' => $this->string('destination_account_name'),
            'piggy_bank_id'            => $this->integer('piggy_bank_id'),

            // native amount and stuff like that:
            'native_amount'            => $this->float('native_amount'),
            'source_amount'            => $this->float('source_amount'),
            'destination_amount'       => $this->float('destination_amount'),

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
            'amount'                   => 'numeric|required|more:0',
            'budget_id'                => 'mustExist:budgets,id|belongsToUser:budgets,id',
            'category'                 => 'between:1,255',
            'source_account_id'        => 'numeric|belongsToUser:accounts,id',
            'source_account_name'      => 'between:1,255',
            'destination_account_id'   => 'numeric|belongsToUser:accounts,id',
            'destination_account_name' => 'between:1,255',
            'piggy_bank_id'            => 'between:1,255',

            // foreign currency amounts
            'native_amount'            => 'numeric|more:0|nullable',
            'source_amount'            => 'numeric|more:0|nullable',
            'destination_amount'       => 'numeric|more:0|nullable',
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
}
