<?php

namespace FireflyIII\JsonApi\V2\Accounts;

use FireflyIII\Models\Account;
use FireflyIII\Support\JsonApi\Concerns\UsergroupAware;
use Illuminate\Support\Facades\Log;
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
        Log::debug(__METHOD__);;
        return [
            ID::make(),
            Attribute::make('name'),
            Attribute::make('active'),
            Attribute::make('order'),
            Attribute::make('last_activity'),
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
        Log::debug(__METHOD__);;
        return [
            // Filter::make('id'),
        ];
    }

    public function repository(): AccountRepository
    {
        Log::debug(__METHOD__);;
        // to access the repository, you need to have the necessary rights.


        $this->setUserGroup($this->server->getUsergroup());
        return AccountRepository::make()
                                ->withServer($this->server)
                                ->withSchema($this)
                                ->withUserGroup($this->userGroup);
    }

}
