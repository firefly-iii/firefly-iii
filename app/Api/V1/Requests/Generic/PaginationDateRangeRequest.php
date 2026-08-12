<?php




declare(strict_types=1);



namespace FireflyIII\Api\V1\Requests\Generic;

use FireflyIII\Api\V1\Requests\AggregateFormRequest;
use FireflyIII\Api\V1\Requests\DateRangeRequest;
use FireflyIII\Api\V1\Requests\PaginationRequest;
use FireflyIII\Models\Transaction;
use Override;

/**
 * TODO this class includes an object type filter which should be moved to its own thing.
 */
class PaginationDateRangeRequest extends AggregateFormRequest
{
    #[Override]
    protected function getRequests(): array
    {
        return [
            DateRangeRequest::class,
            [ObjectTypeApiRequest::class, 'object_type' => Transaction::class],
            [PaginationRequest::class, 'sort_class'     => Transaction::class],
        ];
    }
}
