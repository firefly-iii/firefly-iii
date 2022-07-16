<?php
declare(strict_types=1);
/*
 * AccountTransformer.php
 * Copyright (c) 2022 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace FireflyIII\Transformers\V2;

use FireflyIII\Models\Account;

/**
 * Class AccountTransformer
 */
class AccountTransformer extends AbstractTransformer
{
    /**
     * Transform the account.
     *
     * @param Account $account
     *
     * @return array
     */
    public function transform(Account $account): array
    {
        $fullType    = $account->accountType->type;
        $accountType = (string) config(sprintf('firefly.shortNamesByFullName.%s', $fullType));

        return [
            'id'                      => (string) $account->id,
            'created_at'              => $account->created_at->toAtomString(),
            'updated_at'              => $account->updated_at->toAtomString(),
            'active'                  => $account->active,
            //'order'                   => $order,
            'name'                    => $account->name,
            'type'                    => strtolower($accountType),
//            'account_role'            => $accountRole,
//            'currency_id'             => $currencyId,
//            'currency_code'           => $currencyCode,
//            'currency_symbol'         => $currencySymbol,
//            'currency_decimal_places' => $decimalPlaces,
//            'current_balance'         => number_format((float) app('steam')->balance($account, $date), $decimalPlaces, '.', ''),
//            'current_balance_date'    => $date->toAtomString(),
//            'notes'                   => $this->repository->getNoteText($account),
//            'monthly_payment_date'    => $monthlyPaymentDate,
//            'credit_card_type'        => $creditCardType,
//            'account_number'          => $this->repository->getMetaValue($account, 'account_number'),
            'iban'                    => '' === $account->iban ? null : $account->iban,
//            'bic'                     => $this->repository->getMetaValue($account, 'BIC'),
//            'virtual_balance'         => number_format((float) $account->virtual_balance, $decimalPlaces, '.', ''),
//            'opening_balance'         => $openingBalance,
//            'opening_balance_date'    => $openingBalanceDate,
//            'liability_type'          => $liabilityType,
//            'liability_direction'     => $liabilityDirection,
//            'interest'                => $interest,
//            'interest_period'         => $interestPeriod,
//            'current_debt'            => $this->repository->getMetaValue($account, 'current_debt'),
//            'include_net_worth'       => $includeNetWorth,
//            'longitude'               => $longitude,
//            'latitude'                => $latitude,
//            'zoom_level'              => $zoomLevel,
            'links'                   => [
                [
                    'rel' => 'self',
                    'uri' => '/accounts/' . $account->id,
                ],
            ],
        ];
    }

}
