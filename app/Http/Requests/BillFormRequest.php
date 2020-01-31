<?php
/**
 * BillFormRequest.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Requests;

use FireflyIII\Models\Bill;

/**
 * Class BillFormRequest.
 */
class BillFormRequest extends Request
{
    /**
     * Verify the request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * Returns the data required by the controller.
     *
     * @return array
     */
    public function getBillData(): array
    {
        return [
            'name'          => $this->string('name'),
            'amount_min'    => $this->string('amount_min'),
            'currency_id'   => $this->integer('transaction_currency_id'),
            'currency_code' => '',
            'amount_max'    => $this->string('amount_max'),
            'date'          => $this->date('date'),
            'repeat_freq'   => $this->string('repeat_freq'),
            'skip'          => $this->integer('skip'),
            'notes'         => $this->nlString('notes'),
            'active'        => $this->boolean('active'),
        ];
    }

    /**
     * Rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        $nameRule = 'required|between:1,255|uniqueObjectForUser:bills,name';
        /** @var Bill $bill */
        $bill = $this->route()->parameter('bill');
        if (null !== $bill) {
            $nameRule = 'required|between:1,255|uniqueObjectForUser:bills,name,' . $bill->id;
        }
        // is OK
        $rules = [
            'name'                    => $nameRule,
            'amount_min'              => 'required|numeric|more:0|max:1000000000',
            'amount_max'              => 'required|numeric|more:0|max:1000000000',
            'transaction_currency_id' => 'required|exists:transaction_currencies,id',
            'date'                    => 'required|date',
            'repeat_freq'             => 'required|in:weekly,monthly,quarterly,half-year,yearly',
            'skip'                    => 'required|between:0,31',
            'active'                  => 'boolean',
        ];

        return $rules;
    }
}
