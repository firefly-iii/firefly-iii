<?php




declare(strict_types=1);



namespace FireflyIII\Api\V1\Requests\Models\Account;

use FireflyIII\Api\V1\Requests\AggregateFormRequest;
use FireflyIII\Api\V1\Requests\DateRangeRequest;
use FireflyIII\Api\V1\Requests\DateRequest;
use FireflyIII\Api\V1\Requests\PaginationRequest;
use FireflyIII\Models\Account;

class ShowRequest extends AggregateFormRequest
{
    protected function getRequests(): array
    {
        return [
            [PaginationRequest::class, 'sort_class' => Account::class],
            DateRangeRequest::class,
            DateRequest::class,
            AccountTypeApiRequest::class,
            // [ObjectTypeApiRequest::class, 'object_type' => Account::class],
        ];
    }
}
