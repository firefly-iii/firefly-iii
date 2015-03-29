<?php

namespace FireflyIII\Http\Requests;

use Auth;
use Carbon\Carbon;
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
    public function getBillData()
    {
        return [
            'name'               => $this->get('name'),
            'match'              => $this->get('match'),
            'amount_min'         => floatval($this->get('amount_min')),
            'amount_currency_id' => floatval($this->get('amount_currency_id')),
            'amount_max'         => floatval($this->get('amount_max')),
            'date'               => new Carbon($this->get('date')),
            'user'               => Auth::user()->id,
            'repeat_freq'        => $this->get('repeat_freq'),
            'skip'               => intval($this->get('skip')),
            'automatch'          => intval($this->get('automatch')) === 1,
            'active'             => intval($this->get('active')) === 1,
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        $nameRule = 'required|between:1,255|uniqueForUser:bills,name';
        if (intval(Input::get('id')) > 0) {
            $nameRule = 'required|between:1,255';
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
