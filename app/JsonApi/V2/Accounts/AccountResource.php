<?php

namespace FireflyIII\JsonApi\V2\Accounts;

use FireflyIII\Models\Account;
use Illuminate\Http\Request;
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
        return [
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'name'      => $this->resource->name,
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
