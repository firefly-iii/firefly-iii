<?php

declare(strict_types=1);

namespace FireflyIII\JsonApi\V2\Accounts;

use FireflyIII\Models\Account;
use FireflyIII\Support\JsonApi\Concerns\UsergroupAware;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Core\Schema\Schema;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOne;
use LaravelJsonApi\NonEloquent\Fields\Attribute;
use LaravelJsonApi\NonEloquent\Fields\ID;
use LaravelJsonApi\NonEloquent\Filters\Filter;

class AccountSchema extends Schema
{
    use UsergroupAware;

    /**
     * The model the schema corresponds to.
     */
    public static string $model = Account::class;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        // Log::debug(__METHOD__);

        return [
            ID::make(),
            Attribute::make('created_at'),
            Attribute::make('updated_at'),

            // basic info and meta data
            Attribute::make('name'),
            Attribute::make('active'),
            Attribute::make('order'),
            Attribute::make('iban'),
            Attribute::make('type'),
            Attribute::make('account_role'),
            Attribute::make('account_number'),

            // currency
            Attribute::make('currency_id'),
            Attribute::make('currency_name'),
            Attribute::make('currency_code'),
            Attribute::make('currency_symbol'),
            Attribute::make('currency_decimal_places'),
            Attribute::make('is_multi_currency'),

            // balance
            Attribute::make('balance'),
            Attribute::make('native_balance'),

            // liability things
            Attribute::make('liability_direction'),
            Attribute::make('interest'),
            Attribute::make('interest_period'),
            Attribute::make('current_debt'),

            // dynamic data
            Attribute::make('last_activity'),

            // group
            Attribute::make('object_group_id'),
            Attribute::make('object_group_title'),
            Attribute::make('object_group_order'),

            // relations.
            HasOne::make('user')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        Log::debug(__METHOD__);
        $array  = [];
        $config = config('api.valid_api_filters')[Account::class];
        foreach ($config as $entry) {
            $array[] = Filter::make($entry);
        }
        return $array;
    }

    public function repository(): AccountRepository
    {
        $this->setUserGroup($this->server->getUsergroup());
        $repository = AccountRepository::make()
                                       ->withServer($this->server)
                                       ->withSchema($this)
                                       ->withUserGroup($this->userGroup);
        Log::debug(sprintf('%s: %s', __METHOD__, get_class($repository)));
        return $repository;
    }
}
