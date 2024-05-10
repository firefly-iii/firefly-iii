<?php
/*
 * AccountSchema.php
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

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\Account;
use FireflyIII\Support\Http\Api\ValidatesUserGroupTrait;
use LaravelJsonApi\Core\Schema\Schema;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\NonEloquent\Fields\Attribute;
use LaravelJsonApi\NonEloquent\Fields\ID;
use LaravelJsonApi\NonEloquent\Fields\ToMany;
use LaravelJsonApi\NonEloquent\Fields\ToOne;
use LaravelJsonApi\NonEloquent\Filters\Filter;
use LaravelJsonApi\NonEloquent\Pagination\EnumerablePagination;

class AccountSchema extends Schema
{
    use ValidatesUserGroupTrait;
    protected array                    $acceptedRoles = [UserRoleEnum::READ_ONLY, UserRoleEnum::MANAGE_TRANSACTIONS];

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Account::class;

    /**
     * @inheritDoc
     */
    public function fields(): iterable
    {
        return [
            ID::make(),
            DateTime::make('created_at')->sortable()->readOnly(),
            DateTime::make('updated_at')->sortable()->readOnly(),
            Attribute::make('name')->sortable(),
            Attribute::make('iban'),
            Attribute::make('active'),
            Attribute::make('virtual_balance'),

            Attribute::make('last_activity')->sortable(),
            Attribute::make('balance')->sortable(),
            Attribute::make('native_balance')->sortable(),
            Attribute::make('type'),

            // fancy fields:



            //Attribute::make('current_balance')->sortable(),

            //            'type'                    => strtolower($accountType),
            //            'account_role'            => $accountRole,
            //            'currency_id'             => $currencyId,
            //            'currency_code'           => $currencyCode,
            //            'currency_symbol'         => $currencySymbol,
            //            'currency_decimal_places' => $decimalPlaces,
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
            //             'id'                             => (string) $account->id,
            //            'created_at'                     => $account->created_at->toAtomString(),
            //            'updated_at'                     => $account->updated_at->toAtomString(),
            //            'active'                         => $account->active,
            //            'order'                          => $order,
            //            'name'                           => $account->name,
            //            'iban'                           => '' === (string) $account->iban ? null : $account->iban,
            //            'account_number'                 => $this->accountMeta[$id]['account_number'] ?? null,
            //            'type'                           => strtolower($accountType),
            //            'account_role'                   => $accountRole,
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

            ToOne::make('user'),
//            ToMany::make('tags'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function pagination(): EnumerablePagination
    {
        return EnumerablePagination::make();
    }


    /**
     * @inheritDoc
     */
    public function filters(): iterable
    {
        return [
            Filter::make('name'),
        ];
    }
    public function repository(): AccountRepository
    {
        $userGroup = $this->validateUserGroup(request());
        return AccountRepository::make()
            ->withUserGroup($userGroup)
                             ->withServer($this->server)
                             ->withSchema($this);
    }



}
