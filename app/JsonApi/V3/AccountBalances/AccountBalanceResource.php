<?php

namespace FireflyIII\JsonApi\V3\AccountBalances;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class AccountBalanceResource extends JsonApiResource
{
    /**
     * Get the resource id.
     *
     * @return string
     */
    public function id(): string
    {
        return $this->resource->id;
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
            'name'   => $this->resource->amount,
            'amount' => $this->resource->amount,
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
            $this->relation('account')->withData($this->resource->getAccount()),
        ];
    }

}
