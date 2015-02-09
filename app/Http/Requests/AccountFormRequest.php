<?php

namespace FireflyIII\Http\Requests;

use Auth;
use Config;

/**
 * Class AccountFormRequest
 *
 * @package FireflyIII\Http\Requests
 */
class AccountFormRequest extends Request
{
    public function authorize()
    {
        // Only allow logged in users
        return Auth::check();
    }

    public function rules()
    {
        $accountRoles = join(',', array_keys(Config::get('firefly.accountRoles')));
        $types        = join(',', array_keys(Config::get('firefly.subTitlesByIdentifier')));

        return [
            'name'                => 'required|between:1,100|uniqueForUser:accounts,name',
            'openingBalance'      => 'numeric',
            'openingBalanceDate'  => 'date',
            'accountRole'         => 'in:' . $accountRoles,
            'active'              => 'boolean',
            'balance_currency_id' => 'required|exists:transaction_currencies,id',
            'what'                => 'in:' . $types
        ];
    }
}