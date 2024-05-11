<?php

namespace FireflyIII\JsonApi\V3\AccountBalances;

use FireflyIII\Entities\AccountBalance;
use LaravelJsonApi\Core\Schema\Schema;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOne;
use LaravelJsonApi\NonEloquent\Fields\Attribute;
use LaravelJsonApi\NonEloquent\Fields\ID;

class AccountBalanceSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = AccountBalance::class;

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
            Attribute::make('amount'),
            HasOne::make('account'),
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

    public function repository(): AccountBalanceRepository
    {
        return AccountBalanceRepository::make()
                                       ->withServer($this->server)
                                       ->withSchema($this);
    }
}
