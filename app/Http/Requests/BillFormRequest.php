<?php
/**
 * BillFormRequest.php
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

/**
 * Class BillFormRequest.
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
            'amount_min'                    => $this->string('amount_min'),
            'amount_currency_id_amount_min' => $this->integer('amount_currency_id_amount_min'),
            'amount_currency_id_amount_max' => $this->integer('amount_currency_id_amount_max'),
            'amount_max'                    => $this->string('amount_max'),
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
        // is OK
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
