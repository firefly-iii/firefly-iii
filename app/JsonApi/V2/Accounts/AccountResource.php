<?php

declare(strict_types=1);

namespace FireflyIII\JsonApi\V2\Accounts;

use FireflyIII\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property Account $resource
 */
class AccountResource extends JsonApiResource
{
    /**
     * Get the resource id.
     */
    public function id(): string
    {
        $id = (string) $this->resource->id;
        Log::debug(sprintf('%s: "%s"', __METHOD__, $id));

        return $id;
    }

    /**
     * Get the resource's attributes.
     *
     * @param null|Request $request
     */
    public function attributes($request): iterable
    {
        Log::debug(__METHOD__);

        return [
            'created_at'              => $this->resource->created_at,
            'updated_at'              => $this->resource->updated_at,
            'name'                    => $this->resource->name,
            'active'                  => $this->resource->active,
            'order'                   => $this->resource->order,
            'iban'                    => $this->resource->iban,
            'type'                    => $this->resource->account_type_string,
            'account_role'            => $this->resource->account_role,
            'account_number'          => '' === $this->resource->account_number ? null : $this->resource->account_number,

            // currency (if the account has a currency setting, otherwise NULL).
            'currency_id'             => $this->resource->currency_id,
            'currency_name'           => $this->resource->currency_name,
            'currency_code'           => $this->resource->currency_code,
            'currency_symbol'         => $this->resource->currency_symbol,
            'currency_decimal_places' => $this->resource->currency_decimal_places,
            'is_multi_currency'       => '1' === $this->resource->is_multi_currency,

            // balances
            'balance'                 => $this->resource->balance,
            'native_balance'          => $this->resource->native_balance,

            // liability things
            'liability_direction'     => $this->resource->liability_direction,
            'interest'                => $this->resource->interest,
            'interest_period'         => $this->resource->interest_period,
            'current_debt'            => $this->resource->current_debt, // TODO may be removed in the future.

            // other things
            'last_activity'           => $this->resource->last_activity,


            // object group
            'object_group_id'         => $this->resource->object_group_id,
            'object_group_title'      => $this->resource->object_group_title,
            'object_group_order'      => $this->resource->object_group_order,
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
        ];
    }
}
