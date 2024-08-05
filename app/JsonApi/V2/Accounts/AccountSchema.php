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
use LaravelJsonApi\NonEloquent\Pagination\EnumerablePagination;

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
        Log::debug(__METHOD__);

        return [
            ID::make(),
            Attribute::make('created_at'),
            Attribute::make('updated_at'),

            // basic info and meta data
            Attribute::make('name')->sortable(),
            Attribute::make('active')->sortable(),
            Attribute::make('order')->sortable(),
            Attribute::make('iban')->sortable(),
            Attribute::make('type'),
            Attribute::make('account_role'),
            Attribute::make('account_number')->sortable(),

            // currency
            Attribute::make('currency_id'),
            Attribute::make('currency_name'),
            Attribute::make('currency_code'),
            Attribute::make('currency_symbol'),
            Attribute::make('currency_decimal_places'),
            Attribute::make('is_multi_currency'),

            // balance
            Attribute::make('balance')->sortable(),
            Attribute::make('native_balance')->sortable(),

            // liability things
            Attribute::make('liability_direction'),
            Attribute::make('interest'),
            Attribute::make('interest_period'),
            Attribute::make('current_debt')->sortable(),

            // dynamic data
            Attribute::make('last_activity')->sortable(),
            Attribute::make('balance_difference')->sortable(), // only used for sort.

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
        Log::debug(__METHOD__);
        $this->setUserGroup($this->server->getUsergroup());

        return AccountRepository::make()
            ->withServer($this->server)
            ->withSchema($this)
            ->withUserGroup($this->userGroup)
        ;
    }

    public function pagination(): EnumerablePagination
    {
        Log::debug(__METHOD__);

        return EnumerablePagination::make();
    }
}
