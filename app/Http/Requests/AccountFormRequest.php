<?php
/**
 * AccountFormRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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
            'name'                 => $this->string('name'),
            'active'               => $this->boolean('active'),
            'accountType'          => $this->string('what'),
            'currency_id'          => $this->integer('currency_id'),
            'virtualBalance'       => $this->float('virtualBalance'),
            'iban'                 => $this->string('iban'),
            'BIC'                  => $this->string('BIC'),
            'accountNumber'        => $this->string('accountNumber'),
            'accountRole'          => $this->string('accountRole'),
            'openingBalance'       => $this->float('openingBalance'),
            'openingBalanceDate'   => $this->date('openingBalanceDate'),
            'ccType'               => $this->string('ccType'),
            'ccMonthlyPaymentDate' => $this->string('ccMonthlyPaymentDate'),
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
            'openingBalance'                    => 'numeric|required_with:openingBalanceDate|nullable',
            'openingBalanceDate'                => 'date|required_with:openingBalance|nullable',
            'iban'                              => 'iban|nullable',
            'BIC'                               => 'bic|nullable',
            'virtualBalance'                    => 'numeric|nullable',
            'currency_id'                       => 'exists:transaction_currencies,id',
            'accountNumber'                     => 'between:1,255|uniqueAccountNumberForUser|nullable',
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
