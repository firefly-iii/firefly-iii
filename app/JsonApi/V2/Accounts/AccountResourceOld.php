<?php

declare(strict_types=1);

namespace FireflyIII\JsonApi\V2\Accounts;

use FireflyIII\Models\Account;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property Account $resource
 *
 * This class collects the resources attributes, the account in this case.
 * Generally speaking, each property here is directly related to a property on the account object itself.
 * However, many properties are collected from other sources, like the user or the currency.
 * As a result, the account repository is where it's at, which is where the collection takes place and is optimised.
 */
class AccountResourceOld extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param null|Request $request
     */
    public function attributes($request): iterable
    {
        // fields removed here have been migrated.
        return [
            'created_at'    => $this->resource->created_at,
            'updated_at'    => $this->resource->updated_at,
            'name'          => $this->resource->name,

            //            'virtual_balance' => $this->resource->virtual_balance,
            //            'native_balance'  => $this->resource->native_balance,
            // 'user' => $this->resource->user_array,
            //            'balances' => []
            //

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
            //
            //            // liability stuff
            //            'liability_type'                 => $liabilityType,
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
     * @param null|Request $request
     */
    public function relationships($request): iterable
    {
        return [
            $this->relation('user')->withData($this->resource->user),
            $this->relation('currency')->withData($this->resource->transactionCurrency),
            // $this->relation('account_balances')->withData($this->resource->balances),
        ];
    }
}
