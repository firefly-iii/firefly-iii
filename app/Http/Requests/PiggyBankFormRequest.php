<?php

namespace FireflyIII\Http\Requests;

use Auth;
use Carbon\Carbon;
use FireflyIII\Models\Account;
use Input;
use Navigation;

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

        $nameRule       = 'required|between:1,255|uniquePiggyBankForUser:piggy_banks,name';
        $targetDateRule = 'date';
        if (intval(Input::get('id'))) {
            $nameRule = 'required|between:1,255';
        }

        if (intval(Input::get('repeats')) == 1) {
            $targetDateRule = 'required|date|after:' . date('Y-m-d');
            // switch on rep_every, make sure it's not too far away.
            if (!is_null(Input::get('rep_length'))) {
                $end = Navigation::addPeriod(new Carbon, Input::get('rep_length'), 0);
                $targetDateRule .= '|before:' . $end->format('Y-m-d');
            }
        }


        $rules = [
            'repeats'            => 'required|boolean',
            'name'               => $nameRule,
            'account_id'         => 'required|belongsToUser:accounts',
            'targetamount'       => 'required|min:0.01',
            'amount_currency_id' => 'exists:transaction_currencies,id',
            'startdate'          => 'date',
            'targetdate'         => $targetDateRule,
            'rep_length'         => 'in:day,week,quarter,month,year',
            'rep_every'          => 'integer|min:0|max:31',
            'rep_times'          => 'integer|min:0|max:99',
            'reminder'           => 'in:day,week,quarter,month,year',
            'reminder_skip'      => 'integer|min:0|max:99',
            'remind_me'          => 'boolean|piggyBankReminder',
            'order'              => 'integer|min:1',

        ];

        return $rules;
    }
}