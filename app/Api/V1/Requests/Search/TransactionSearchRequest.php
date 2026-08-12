<?php




declare(strict_types=1);


namespace FireflyIII\Api\V1\Requests\Search;

use FireflyIII\Api\V1\Requests\AggregateFormRequest;
use FireflyIII\Api\V1\Requests\PaginationRequest;
use FireflyIII\Models\TransactionJournal;
use Override;

class TransactionSearchRequest extends AggregateFormRequest
{
    #[Override]
    protected function getRequests(): array
    {
        return [
            [PaginationRequest::class, 'sort_class' => TransactionJournal::class],
            SearchQueryRequest::class,
            // [ObjectTypeApiRequest::class, 'object_type' => Account::class],
        ];
    }
}
