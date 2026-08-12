<?php




declare(strict_types=1);



namespace FireflyIII\Api\V1\Requests\Summary;

use FireflyIII\Api\V1\Requests\AggregateFormRequest;
use FireflyIII\Api\V1\Requests\DateRangeRequest;
use FireflyIII\Api\V1\Requests\Models\TransactionCurrency\CurrencyCodeRequest;

class BasicRequest extends AggregateFormRequest
{
    protected function getRequests(): array
    {
        return [[DateRangeRequest::class, 'required'], CurrencyCodeRequest::class];
    }
}
