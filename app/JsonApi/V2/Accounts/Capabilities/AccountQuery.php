<?php
/*
 * AccountQuery.php
 * Copyright (c) 2024 james@firefly-iii.org.
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\JsonApi\V2\Accounts\Capabilities;

use FireflyIII\Models\Account;
use FireflyIII\Support\Http\Api\AccountFilter;
use FireflyIII\Support\JsonApi\CollectsCustomParameters;
use FireflyIII\Support\JsonApi\Concerns\UsergroupAware;
use FireflyIII\Support\JsonApi\Enrichments\AccountEnrichment;
use FireflyIII\Support\JsonApi\ExpandsQuery;
use FireflyIII\Support\JsonApi\FiltersPagination;
use FireflyIII\Support\JsonApi\SortsCollection;
use FireflyIII\Support\JsonApi\SortsQueryResults;
use FireflyIII\Support\JsonApi\ValidateSortParameters;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Contracts\Store\HasPagination;
use LaravelJsonApi\NonEloquent\Capabilities\QueryAll;

class AccountQuery extends QueryAll implements HasPagination
{
    use AccountFilter;
    use CollectsCustomParameters;
    use ExpandsQuery;
    use FiltersPagination;
    use SortsCollection;
    use SortsQueryResults;
    use UsergroupAware;
    use ValidateSortParameters;

    // use PaginatesEnumerables;

    #[\Override]
    /**
     * This method returns all accounts, given a bunch of filters and sort fields, together with pagination.
     *
     * It is only used on the index, and nowhere else.
     */
    public function get(): iterable
    {
        Log::debug(__METHOD__);
        // collect sort options
        $sort        = $this->queryParameters->sortFields();

        // collect pagination based on the page
        $pagination  = $this->filtersPagination($this->queryParameters->page());

        // check if we need all accounts, regardless of pagination
        // This is necessary when the user wants to sort on specific params.
        $needsAll    = $this->needsFullDataset(Account::class, $sort);

        // params that were not recognised, may be my own custom stuff.
        $otherParams = $this->getOtherParams($this->queryParameters->unrecognisedParameters());

        // start the query
        $query       = $this->userGroup->accounts();

        // add sort and filter parameters to the query.
        $query       = $this->addSortParams(Account::class, $query, $sort);
        $query       = $this->addFilterParams(Account::class, $query, $this->queryParameters->filter());

        // collect the result.
        $collection  = $query->get(['accounts.*']);
        // sort the data after the query, and return it right away.
        $collection  = $this->sortCollection(Account::class, $collection, $sort);

        // if the entire collection needs to be enriched and sorted, do so now:
        $totalCount  = $collection->count();
        Log::debug(sprintf('Total is %d', $totalCount));
        if ($needsAll) {
            Log::debug('Needs the entire collection');
            // enrich the entire collection
            $enrichment  = new AccountEnrichment();
            $enrichment->setStart($otherParams['start'] ?? null);
            $enrichment->setEnd($otherParams['end'] ?? null);
            $collection  = $enrichment->enrich($collection);

            // TODO sort the set based on post-query sort options:
            $collection  = $this->postQuerySort(Account::class, $collection, $sort);

            // take the current page from the enriched set.
            $currentPage = $collection->skip(($pagination['number'] - 1) * $pagination['size'])->take($pagination['size']);
        }
        if (!$needsAll) {
            Log::debug('Needs only partial collection');
            // take from the collection the filtered page + page number:
            $currentPage = $collection->skip(($pagination['number'] - 1) * $pagination['size'])->take($pagination['size']);

            // enrich only the current page.
            $enrichment  = new AccountEnrichment();
            $enrichment->setStart($otherParams['start'] ?? null);
            $enrichment->setEnd($otherParams['end'] ?? null);
            $currentPage = $enrichment->enrich($currentPage);
        }
        // get current page?
        Log::debug(sprintf('Skip %d, take %d', ($pagination['number'] - 1) * $pagination['size'], $pagination['size']));
        // $currentPage = $collection->skip(($pagination['number'] - 1) * $pagination['size'])->take($pagination['size']);
        Log::debug(sprintf('New collection size: %d', $currentPage->count()));

        // TODO add filters after the query, if there are filters that cannot be applied to the database
        // TODO same for sort things.

        return new LengthAwarePaginator($currentPage, $totalCount, $pagination['size'], $pagination['number']);
    }

    #[\Override]
    public function paginate(array $page): Page
    {
        exit('here weare');
        // TODO: Implement paginate() method.
    }

    #[\Override]
    public function getOrPaginate(?array $page): iterable
    {
        exit('here weare');
        // TODO: Implement getOrPaginate() method.
    }
}
