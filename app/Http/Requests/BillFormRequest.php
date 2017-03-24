<?php
/**
 * BillFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

/**
 * Class BillFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class BillFormRequest extends Request
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
    public function getBillData()
    {
        return [
            'name'                          => $this->string('name'),
            'match'                         => $this->string('match'),
            'amount_min'                    => $this->float('amount_min'),
            'amount_currency_id_amount_min' => $this->integer('amount_currency_id_amount_min'),
            'amount_currency_id_amount_max' => $this->integer('amount_currency_id_amount_max'),
            'amount_max'                    => $this->float('amount_max'),
            'date'                          => $this->date('date'),
            'repeat_freq'                   => $this->string('repeat_freq'),
            'skip'                          => $this->integer('skip'),
            'automatch'                     => $this->boolean('automatch'),
            'active'                        => $this->boolean('active'),
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        $nameRule  = 'required|between:1,255|uniqueObjectForUser:bills,name';
        $matchRule = 'required|between:1,255|uniqueObjectForUser:bills,match';
        if (intval($this->get('id')) > 0) {
            $nameRule  .= ',' . intval($this->get('id'));
            $matchRule .= ',' . intval($this->get('id'));
        }

        $rules = [
            'name'                          => $nameRule,
            'match'                         => $matchRule,
            'amount_min'                    => 'required|numeric|more:0',
            'amount_max'                    => 'required|numeric|more:0',
            'amount_currency_id_amount_min' => 'required|exists:transaction_currencies,id',
            'amount_currency_id_amount_max' => 'required|exists:transaction_currencies,id',
            'date'                          => 'required|date',
            'repeat_freq'                   => 'required|in:weekly,monthly,quarterly,half-year,yearly',
            'skip'                          => 'required|between:0,31',
            'automatch'                     => 'in:1',
            'active'                        => 'in:1',
        ];

        return $rules;
    }
}
