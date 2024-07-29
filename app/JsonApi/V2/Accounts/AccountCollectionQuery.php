<?php

declare(strict_types=1);

namespace FireflyIII\JsonApi\V2\Accounts;

use FireflyIII\Models\Account;
use FireflyIII\Rules\IsAllowedGroupAction;
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

        return [
            'fields'        => [
                'nullable',
                'array',
                JsonApiRule::fieldSets(),
            ],
            'user_group_id' => [
                'nullable',
                'integer',
                new IsAllowedGroupAction(Account::class, request()->method()),
            ],
            'filter'        => [
                'nullable',
                'array',
                JsonApiRule::filter(),
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
