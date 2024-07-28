<?php

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
        Log::debug(__METHOD__);
        return [
            'created_at'    => $this->resource->created_at,
            'updated_at'    => $this->resource->updated_at,
            'name'          => $this->resource->name,
            'active'        => $this->resource->active,
            'order'         => $this->resource->order,
            'type'          => $this->resource->account_type_string,
            'account_role'          => $this->resource->account_role,
            'account_number'          => '' === $this->resource->account_number ? null : $this->resource->account_number,

            // currency
            'currency_id'             => $this->resource->currency_id,
            'currency_name'           => $this->resource->currency_name,
            'currency_code'           => $this->resource->currency_code,
            'currency_symbol'         => $this->resource->currency_symbol,
            'currency_decimal_places' => $this->resource->currency_decimal_places,

            // liability things
            'liability_direction'           => $this->resource->liability_direction,
            'interest'                      => $this->resource->interest,
            'interest_period'               => $this->resource->interest_period,
            'current_debt'                  => $this->resource->current_debt,


            'last_activity' => $this->resource->last_activity,
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
            $this->relation('user')->withData($this->resource->user),
        ];
    }

}
