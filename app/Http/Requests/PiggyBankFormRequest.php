<?php
/**
 * PiggyBankFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Requests;

use Auth;
use Input;

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
        return Auth::check();
    }

    /**
     * @return array
     */
    public function rules()
    {

        $nameRule       = 'required|between:1,255|uniquePiggyBankForUser';
        $targetDateRule = 'date';
        if (intval(Input::get('id'))) {
            $nameRule = 'required|between:1,255|uniquePiggyBankForUser:' . intval(Input::get('id'));
        }


        $rules = [
            'name'                            => $nameRule,
            'account_id'                      => 'required|belongsToUser:accounts',
            'targetamount'                    => 'required|min:0.01',
            'amount_currency_id_targetamount' => 'exists:transaction_currencies,id',
            'startdate'                       => 'date',
            'targetdate'                      => $targetDateRule,
            'order'                           => 'integer|min:1',

        ];

        return $rules;
    }
}
