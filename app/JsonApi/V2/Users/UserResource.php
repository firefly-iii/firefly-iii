<?php

declare(strict_types=1);

namespace FireflyIII\JsonApi\V2\Users;

use FireflyIII\Models\User;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property User $resource
 */
class UserResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param null|Request $request
     */
    public function attributes($request): iterable
    {
        return [
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'email'      => $this->resource->email,
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
            // @TODO
        ];
    }
}
