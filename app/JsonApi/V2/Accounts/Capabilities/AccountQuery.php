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
use FireflyIII\Support\JsonApi\ValidateSortParameters;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Contracts\Store\HasPagination;
use LaravelJsonApi\NonEloquent\Capabilities\QueryAll;
use LaravelJsonApi\NonEloquent\Concerns\PaginatesEnumerables;

class AccountQuery extends QueryAll implements HasPagination
{
    use ExpandsQuery;
    use FiltersPagination;
    use PaginatesEnumerables;
    use SortsCollection;
    use UsergroupAware;
    use ValidateSortParameters;
    use CollectsCustomParameters;
    use AccountFilter;

    #[\Override]
    /**
     * This method returns all accounts, given a bunch of filters and sort fields, together with pagination.
     *
     * It is only used on the index, and nowhere else.
     */
    public function get(): iterable
    {
        Log::debug(__METHOD__);
        // collect filters
        $filters = $this->queryParameters->filter();

        // collect sort options
        $sort = $this->queryParameters->sortFields();

        // collect pagination based on the page
        $pagination = $this->filtersPagination($this->queryParameters->page());
        // check if we need all accounts, regardless of pagination
        // This is necessary when the user wants to sort on specific params.
        $needsAll = $this->needsFullDataset('account', $sort);

        // params that were not recognised, may be my own custom stuff.
        $otherParams = $this->getOtherParams($this->queryParameters->unrecognisedParameters());

        // start the query
        $query = $this->userGroup->accounts();

        // add pagination to the query, limiting the results.
        if (!$needsAll) {
            Log::debug('Need full dataset');
            $query = $this->addPagination($query, $pagination);
        }

        // add sort and filter parameters to the query.
        $query = $this->addSortParams(Account::class, $query, $sort);
        $query = $this->addFilterParams(Account::class, $query, $filters);

        // collect the result.
        $collection = $query->get(['accounts.*']);

        // enrich the collected data
        $enrichment = new AccountEnrichment();
        $enrichment->setStart($otherParams['start'] ?? null);
        $enrichment->setEnd($otherParams['end'] ?? null);
        $collection = $enrichment->enrich($collection);

        // TODO add filters after the query, if there are filters that cannot be applied to the database
        // TODO same for sort things.

        // sort the data after the query, and return it right away.
        return $this->sortCollection(Account::class, $collection, $sort);
    }
}
