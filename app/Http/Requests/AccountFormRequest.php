<?php

namespace FireflyIII\Http\Requests;

use Auth;
use Config;
use FireflyIII\Models\Account;
use Input;

/**
 * Class AccountFormRequest
 *
 * @package FireflyIII\Http\Requests
 */
class AccountFormRequest extends Request
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
        $accountRoles = join(',', array_keys(Config::get('firefly.accountRoles')));
        $types        = join(',', array_keys(Config::get('firefly.subTitlesByIdentifier')));

        $nameRule = 'required|between:1,100|uniqueForUser:accounts,name';
        if (Account::find(Input::get('id'))) {
            $nameRule = 'required|between:1,100|belongsToUser:accounts';
        }

        return [
            'name'                => $nameRule,
            'openingBalance'      => 'numeric',
            'openingBalanceDate'  => 'date',
            'accountRole'         => 'in:' . $accountRoles,
            'active'              => 'boolean',
            'balance_currency_id' => 'exists:transaction_currencies,id',
            'what'                => 'in:' . $types
        ];
    }
}
