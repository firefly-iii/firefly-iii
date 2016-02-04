<?php

namespace FireflyIII\Http\Requests;

use Auth;

/**
 * Class NewUserFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class NewUserFormRequest extends Request
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
        return [
            'bank_name'                            => 'required|between:1,200',
            'bank_balance'                         => 'required|numeric',
            'savings_balance'                      => 'numeric',
            'credit_card_limit'                    => 'numeric',
            'amount_currency_id_bank_balance'      => 'exists:transaction_currencies,id',
            'amount_currency_id_savings_balance'   => 'exists:transaction_currencies,id',
            'amount_currency_id_credit_card_limit' => 'exists:transaction_currencies,id',
        ];
    }
}
