<?php

namespace FireflyIII\JsonApi\V2\Accounts;

use FireflyIII\Models\Account;
use FireflyIII\Support\JsonApi\Concerns\UsergroupAware;
use LaravelJsonApi\Core\Schema\Schema;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOne;
use LaravelJsonApi\NonEloquent\Fields\Attribute;
use LaravelJsonApi\NonEloquent\Fields\ID;
use LaravelJsonApi\NonEloquent\Fields\Relation;


class AccountSchema extends Schema
{
    use UsergroupAware;

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Account::class;


    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Attribute::make('name'),
            HasOne::make('user')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            // Filter::make('id'),
        ];
    }

    public function repository(): AccountRepository
    {
        $this->setUserGroup($this->server->getUsergroup());
        return AccountRepository::make()
                                ->withServer($this->server)
                                ->withSchema($this)
                                ->withUserGroup($this->userGroup);
    }

}
