<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Requests\Autocomplete;

use FireflyIII\Api\V1\Requests\AggregateFormRequest;
use FireflyIII\Api\V1\Requests\DateRequest;
use FireflyIII\Api\V1\Requests\Generic\ObjectTypeApiRequest;
use FireflyIII\Api\V1\Requests\Generic\QueryRequest;
use FireflyIII\Api\V1\Requests\PaginationRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use Override;

class AutocompleteTransactionApiRequest extends AggregateFormRequest
{
    #[Override]
    protected function getRequests(): array
    {
        return [
            DateRequest::class,
            [PaginationRequest::class, 'sort_class'     => Account::class],
            [ObjectTypeApiRequest::class, 'object_type' => Transaction::class],
            QueryRequest::class,
        ];
    }
}
