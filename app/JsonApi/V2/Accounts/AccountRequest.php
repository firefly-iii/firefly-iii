<?php

declare(strict_types=1);

namespace FireflyIII\JsonApi\V2\Accounts;

use FireflyIII\Rules\BelongsUser;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class AccountRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        Log::debug(__METHOD__);

        exit('am i used');

        return [
            'type' => [
                new BelongsUser(),
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
