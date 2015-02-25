<?php

namespace FireflyIII\Http\Requests;

use Auth;
use FireflyIII\Models\Account;
use Input;

/**
 * Class PiggyBankFormRequest
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

        $nameRule = 'required|between:1,255|uniqueForUser:piggy_banks,name';
        if (intval(Input::get('id'))) {
            $nameRule = 'required|between:1,255';
        }

        $rules = [
            'account_id'    => 'required|belongsToUser:accounts',
            'name'          => $nameRule,
            'targetamount'  => 'required|min:0.01',
            'startdate'     => 'date',
            'targetdate'    => 'date',
            'repeats'       => 'required|boolean',
            'rep_length'    => 'in:day,week,quarter,month,year',
            'rep_every'     => 'integer|min:0|max:31',
            'rep_times'     => 'integer|min:0|max:99',
            'reminder'      => 'in:day,week,quarter,month,year',
            'reminder_skip' => 'integer|min:0|max:99',
            'remind_me'     => 'boolean',
            'order'         => 'integer|min:1',

        ];

        return $rules;
    }
}