<?php
/**
 * PiggyBankFormRequest.php
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

use Carbon\Carbon;

/**
 * Class PiggyBankFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class PiggyBankFormRequest extends Request
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
    public function getPiggyBankData(): array
    {
        return [
            'name'         => $this->string('name'),
            'startdate'    => new Carbon,
            'account_id'   => $this->integer('account_id'),
            'targetamount' => $this->float('targetamount'),
            'targetdate'   => $this->date('targetdate'),
            'note'         => $this->string('note'),
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {

        $nameRule = 'required|between:1,255|uniquePiggyBankForUser';
        if (intval($this->get('id'))) {
            $nameRule = 'required|between:1,255|uniquePiggyBankForUser:' . intval($this->get('id'));
        }


        $rules = [
            'name'                            => $nameRule,
            'account_id'                      => 'required|belongsToUser:accounts',
            'targetamount'                    => 'required|numeric|more:0',
            'amount_currency_id_targetamount' => 'required|exists:transaction_currencies,id',
            'startdate'                       => 'date',
            'targetdate'                      => 'date|nullable',
            'order'                           => 'integer|min:1',

        ];

        return $rules;
    }
}
