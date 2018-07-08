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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
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
    public function authorize(): bool
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getBillData(): array
    {
        return [
            'name'                    => $this->string('name'),
            'amount_min'              => $this->string('amount_min'),
            'transaction_currency_id' => $this->integer('transaction_currency_id'),
            'amount_max'              => $this->string('amount_max'),
            'date'                    => $this->date('date'),
            'repeat_freq'             => $this->string('repeat_freq'),
            'skip'                    => $this->integer('skip'),
            'notes'                   => $this->string('notes'),
            'active'                  => $this->boolean('active'),
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $nameRule = 'required|between:1,255|uniqueObjectForUser:bills,name';
        if ($this->integer('id') > 0) {
            // todo is a fix to do this better.
            $nameRule .= ',' . $this->integer('id');
        }
        // is OK
        $rules = [
            'name'                    => $nameRule,
            'amount_min'              => 'required|numeric|more:0',
            'amount_max'              => 'required|numeric|more:0',
            'transaction_currency_id' => 'required|exists:transaction_currencies,id',
            'date'                    => 'required|date',
            'repeat_freq'             => 'required|in:weekly,monthly,quarterly,half-year,yearly',
            'skip'                    => 'required|between:0,31',
            'active'                  => 'boolean',
        ];

        return $rules;
    }
}
