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

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

use FireflyIII\Repositories\Account\AccountRepositoryInterface;

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
    public function getAccountData(): array
    {
        return [
            'name'                   => $this->string('name'),
            'active'                 => $this->boolean('active'),
            'accountType'            => $this->string('what'),
            'currency_id'            => $this->integer('currency_id'),
            'virtualBalance'         => $this->float('virtualBalance'),
            'iban'                   => $this->string('iban'),
            'BIC'                    => $this->string('BIC'),
            'accountNumber'          => $this->string('accountNumber'),
            'accountRole'            => $this->string('accountRole'),
            'openingBalance'         => $this->float('openingBalance'),
            'openingBalanceDate'     => $this->date('openingBalanceDate'),
            'ccType'                 => $this->string('ccType'),
            'ccMonthlyPaymentDate'   => $this->string('ccMonthlyPaymentDate'),
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        /** @var AccountRepositoryInterface $repository */
        $repository     = app(AccountRepositoryInterface::class);
        $accountRoles   = join(',', config('firefly.accountRoles'));
        $types          = join(',', array_keys(config('firefly.subTitlesByIdentifier')));
        $ccPaymentTypes = join(',', array_keys(config('firefly.ccTypes')));

        $nameRule = 'required|min:1|uniqueAccountForUser';
        $idRule   = '';
        if (!is_null($repository->find(intval($this->get('id')))->id)) {
            $idRule   = 'belongsToUser:accounts';
            $nameRule = 'required|min:1|uniqueAccountForUser:' . intval($this->get('id'));
        }

        return [
            'id'                                => $idRule,
            'name'                              => $nameRule,
            'openingBalance'                    => 'numeric|required_with:openingBalanceDate',
            'openingBalanceDate'                => 'date|required_with:openingBalance',
            'iban'                              => 'iban',
            'BIC'                               => 'bic',
            'virtualBalance'                    => 'numeric',
            'currency_id'                       => 'exists:transaction_currencies,id',
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
