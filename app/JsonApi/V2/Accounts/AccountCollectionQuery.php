<?php

declare(strict_types=1);

namespace FireflyIII\JsonApi\V2\Accounts;

use FireflyIII\Models\Account;
use FireflyIII\Rules\Account\IsValidAccountType;
use FireflyIII\Rules\IsAllowedGroupAction;
use FireflyIII\Rules\IsDateOrTime;
use FireflyIII\Rules\IsValidDateRange;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class AccountCollectionQuery extends ResourceQuery
{
    /**
     * Get the validation rules that apply to the request query parameters.
     */
    public function rules(): array
    {
        Log::debug(__METHOD__);
        $validFilters = config('api.valid_api_filters')[Account::class];

        return [
            'fields'        => [
                'nullable',
                'array',
                JsonApiRule::fieldSets(),
            ],
            'userGroupId'   => [
                'nullable',
                'integer',
                new IsAllowedGroupAction(Account::class, request()->method()),
            ],
            'startPeriod'   => [
                'nullable',
                'date',
                new IsDateOrTime(),
                new IsValidDateRange(),
            ],
            'endPeriod'     => [
                'nullable',
                'date',
                new IsDateOrTime(),
                new IsValidDateRange(),
            ],
            'filter'        => [
                'nullable',
                'array',
                JsonApiRule::filter($validFilters),
                new IsValidAccountType(),
            ],
            'include'       => [
                'nullable',
                'string',
                JsonApiRule::includePaths(),
            ],
            'page'          => [
                'nullable',
                'array',
                JsonApiRule::page(),
            ],
            'sort'          => [
                'nullable',
                'string',
                JsonApiRule::sort(),
            ],
            'withCount'     => [
                'nullable',
                'string',
                JsonApiRule::countable(),
            ],
        ];
    }
}
