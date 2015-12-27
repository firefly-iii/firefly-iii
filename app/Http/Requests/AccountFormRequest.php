<?php

namespace FireflyIII\Http\Requests;

use Auth;
use Config;
use FireflyIII\Models\Account;
use Input;

/**
 * Class AccountFormRequest
 *
 * @codeCoverageIgnore
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
        $accountRoles   = join(',', array_keys(Config::get('firefly.accountRoles')));
        $types          = join(',', array_keys(Config::get('firefly.subTitlesByIdentifier')));
        $ccPaymentTypes = join(',', array_keys(Config::get('firefly.ccTypes')));

        $nameRule = 'required|min:1|uniqueAccountForUser';
        $idRule   = '';
        if (Account::find(Input::get('id'))) {
            $idRule   = 'belongsToUser:accounts';
            $nameRule = 'required|min:1|uniqueAccountForUser:' . Input::get('id');
        }

        return [
            'id'                                => $idRule,
            'name'                              => $nameRule,
            'openingBalance'                    => 'numeric',
            'iban'                              => 'iban',
            'virtualBalance'                    => 'numeric',
            'openingBalanceDate'                => 'date',
            'accountRole'                       => 'in:' . $accountRoles,
            'active'                            => 'boolean',
            'ccType'                            => 'in:' . $ccPaymentTypes,
            'ccMonthlyPaymentDate'              => 'date',
            'amount_currency_id_openingBalance' => 'exists:transaction_currencies,id',
            'amount_currency_id_virtualBalance' => 'exists:transaction_currencies,id',
            'what'                              => 'in:' . $types
        ];
    }
}
