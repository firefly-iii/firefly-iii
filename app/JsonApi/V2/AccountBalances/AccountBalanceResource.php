<?php

declare(strict_types=1);

namespace FireflyIII\JsonApi\V2\AccountBalances;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class AccountBalanceResource extends JsonApiResource
{
    /**
     * Get the resource id.
     */
    public function id(): string
    {
        return $this->resource->id;
    }

    /**
     * Get the resource's attributes.
     *
     * @param null|Request $request
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
     * @param null|Request $request
     */
    public function relationships($request): iterable
    {
        return [
            $this->relation('account')->withData($this->resource->getAccount()),
        ];
    }
}
