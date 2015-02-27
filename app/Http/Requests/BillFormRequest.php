<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 25/02/15
 * Time: 12:29
 */

namespace FireflyIII\Http\Requests;

use Auth;
use Input;

/**
 * Class BillFormRequest
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
        return Auth::check();
    }

    /**
     * @return array
     */
    public function rules()
    {
        $nameRule = 'required|between:1,255|uniqueForUser:bills,name';
        if(intval(Input::get('id')) > 0) {
            $nameRule .= ','.intval(Input::get('id'));
        }

        $rules = [
            'name'               => $nameRule,
            'match'              => 'required|between:1,255',
            'amount_min'         => 'required|numeric|min:0.01',
            'amount_max'         => 'required|numeric|min:0.01',
            'amount_currency_id' => 'required|exists:transaction_currencies,id',
            'date'               => 'required|date',
            'repeat_freq'        => 'required|in:weekly,monthly,quarterly,half-year,yearly',
            'skip'               => 'required|between:0,31',
            'automatch'          => 'in:1',
            'active'             => 'in:1',
        ];

        return $rules;
    }
}