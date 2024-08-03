<?php

namespace FireflyIII\JsonApi\V2\Accounts;

use FireflyIII\Rules\BelongsUser;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class AccountRequest extends ResourceRequest
{

    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        Log::debug(__METHOD__);
        die('am i used');
        return [
            'type' => [
                new BelongsUser()
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

}
