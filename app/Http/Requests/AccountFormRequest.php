<?php
/**
 * AccountFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Requests;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use Input;

/**
 * Class AccountFormRequest
 *
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
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getAccountDate(): array
    {
        return [
            'name'                   => trim($this->input('name')),
            'active'                 => intval($this->input('active')) === 1,
            'accountType'            => $this->input('what'),
            'virtualBalance'         => round($this->input('virtualBalance'), 2),
            'virtualBalanceCurrency' => intval($this->input('amount_currency_id_virtualBalance')),
            'user'                   => auth()->user()->id,
            'iban'                   => trim($this->input('iban')),
            'accountNumber'          => trim($this->input('accountNumber')),
            'accountRole'            => $this->input('accountRole'),
            'openingBalance'         => round($this->input('openingBalance'), 2),
            'openingBalanceDate'     => new Carbon((string)$this->input('openingBalanceDate')),
            'openingBalanceCurrency' => intval($this->input('amount_currency_id_openingBalance')),
            'ccType'                 => $this->input('ccType'),
            'ccMonthlyPaymentDate'   => $this->input('ccMonthlyPaymentDate'),
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        $accountRoles   = join(',', array_keys(config('firefly.accountRoles')));
        $types          = join(',', array_keys(config('firefly.subTitlesByIdentifier')));
        $ccPaymentTypes = join(',', array_keys(config('firefly.ccTypes')));

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
            'accountNumber'                     => 'between:1,255|uniqueAccountNumberForUser',
            'accountRole'                       => 'in:' . $accountRoles,
            'active'                            => 'boolean',
            'ccType'                            => 'in:' . $ccPaymentTypes,
            'ccMonthlyPaymentDate'              => 'date',
            'amount_currency_id_openingBalance' => 'exists:transaction_currencies,id',
            'amount_currency_id_virtualBalance' => 'exists:transaction_currencies,id',
            'what'                              => 'in:' . $types,
        ];
    }
}
