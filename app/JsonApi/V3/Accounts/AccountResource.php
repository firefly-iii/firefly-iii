<?php

namespace FireflyIII\JsonApi\V3\Accounts;

use FireflyIII\Models\Account;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property Account $resource
 */
class AccountResource extends JsonApiResource
{

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
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'name'       => $this->resource->name,
            'account_type' => $this->resource->accountType->type,
            'virtual_balance' => $this->resource->virtual_balance,
            'iban' => $this->resource->iban,
            'active' => $this->resource->active,
            'order' => $this->resource->order,
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
            'user' => $this->relation('user')
        ];
    }

}
