<?php
/*
 * AccountResource.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\JsonApi\V3\Accounts;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class AccountResource extends JsonApiResource
{

    /**
     * Get the resource id.
     *
     * @return string
     */
    public function id(): string
    {
        return (string) $this->resource->id;
    }

    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     *
     * @return iterable
     */
    public function attributes($request): iterable
    {
        return [
            'created_at'      => $this->resource->created_at,
            'updated_at'      => $this->resource->updated_at,
            'name'            => $this->resource->name,
            'iban'            => '' === $this->resource->iban ? null : $this->resource->iban,
            'active'          => $this->resource->active,
//            'virtual_balance' => $this->resource->virtual_balance,
            'last_activity'   => $this->resource->last_activity,
//            'native_balance'  => $this->resource->native_balance,
            'type'            => $this->resource->type,
            'account_role'    => $this->resource->account_role,
            //'user' => $this->resource->user_array,
//            'balances' => []
//
            // currency
//            'currency_id'             => $this->resource->currency_id,
//            'currency_code'           => $this->resource->currency_code,
//            'currency_symbol'         => $this->resource->currency_symbol,
//            'currency_decimal_places' => $this->resource->currency_decimal_places,

            // balance (in currency, on date)
//            'current_balance'         => $this->resource->current_balance,


            //            'current_balance'         => app('steam')->bcround(app('steam')->balance($account, $date), $decimalPlaces),
            //            'current_balance_date'    => $date->toAtomString(),
            //            'notes'                   => $this->repository->getNoteText($account),
            //            'monthly_payment_date'    => $monthlyPaymentDate,
            //            'credit_card_type'        => $creditCardType,
            //            'account_number'          => $this->repository->getMetaValue($account, 'account_number'),
            //            'bic'                     => $this->repository->getMetaValue($account, 'BIC'),
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

            //            'order'                          => $order,

            //            'currency_id'                    => (string) $currency->id,
            //            'currency_code'                  => $currency->code,
            //            'currency_symbol'                => $currency->symbol,
            //            'currency_decimal_places'        => $currency->decimal_places,
            //
            //            'native_currency_id'             => (string) $this->default->id,
            //            'native_currency_code'           => $this->default->code,
            //            'native_currency_symbol'         => $this->default->symbol,
            //            'native_currency_decimal_places' => $this->default->decimal_places,
            //
            //            // balance:
            //            'current_balance'                => $balance,
            //            'native_current_balance'         => $nativeBalance,
            //            'current_balance_date'           => $this->getDate()->endOfDay()->toAtomString(),
            //
            //            // balance difference
            //            'balance_difference'             => $balanceDiff,
            //            'native_balance_difference'      => $nativeBalanceDiff,
            //            'balance_difference_start'       => $diffStart,
            //            'balance_difference_end'         => $diffEnd,
            //
            //            // more meta
            //            'last_activity'                  => array_key_exists($id, $this->lastActivity) ? $this->lastActivity[$id]->toAtomString() : null,
            //
            //            // liability stuff
            //            'liability_type'                 => $liabilityType,
            //            'liability_direction'            => $liabilityDirection,
            //            'interest'                       => $interest,
            //            'interest_period'                => $interestPeriod,
            //            'current_debt'                   => $currentDebt,
            //
            //            // object group
            //            'object_group_id'                => null !== $objectGroupId ? (string) $objectGroupId : null,
            //            'object_group_order'             => $objectGroupOrder,
            //            'object_group_title'             => $objectGroupTitle,
            //            'notes'                   => $this->repository->getNoteText($account),
            //            'monthly_payment_date'    => $monthlyPaymentDate,
            //            'credit_card_type'        => $creditCardType,
            //            'bic'                     => $this->repository->getMetaValue($account, 'BIC'),
            //            'virtual_balance'         => number_format((float) $account->virtual_balance, $decimalPlaces, '.', ''),
            //            'opening_balance'         => $openingBalance,
            //            'opening_balance_date'    => $openingBalanceDate,
            //            'include_net_worth'       => $includeNetWorth,
            //            'longitude'               => $longitude,
            //            'latitude'                => $latitude,
            //            'zoom_level'              => $zoomLevel,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     *
     * @return iterable
     */
    public function relationships($request): iterable
    {
        return [
            $this->relation('user')->withData($this->resource->user_array),
            $this->relation('balances')->withData([]),
            //$this->relation('tags')->withData($this->resource->getTags()),
        ];
    }
}
