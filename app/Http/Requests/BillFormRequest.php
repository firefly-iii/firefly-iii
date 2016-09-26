<?php
/**
 * BillFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Requests;

use Carbon\Carbon;
use Input;

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
            'name'                          => $this->get('name'),
            'match'                         => $this->get('match'),
            'amount_min'                    => round($this->get('amount_min'), 2),
            'amount_currency_id_amount_min' => intval($this->get('amount_currency_id_amount_min')),
            'amount_currency_id_amount_max' => intval($this->get('amount_currency_id_amount_max')),
            'amount_max'                    => round($this->get('amount_max'), 2),
            'date'                          => new Carbon($this->get('date')),
            'user'                          => auth()->user()->id,
            'repeat_freq'                   => $this->get('repeat_freq'),
            'skip'                          => intval($this->get('skip')),
            'automatch'                     => intval($this->get('automatch')) === 1,
            'active'                        => intval($this->get('active')) === 1,
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        $nameRule  = 'required|between:1,255|uniqueObjectForUser:bills,name';
        $matchRule = 'required|between:1,255|uniqueObjectForUser:bills,match';
        if (intval(Input::get('id')) > 0) {
            $nameRule .= ',' . intval(Input::get('id'));
            $matchRule .= ',' . intval(Input::get('id'));
        }

        $rules = [
            'name'                          => $nameRule,
            'match'                         => $matchRule,
            'amount_min'                    => 'required|numeric|min:0.01',
            'amount_max'                    => 'required|numeric|min:0.01',
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
