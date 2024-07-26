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

use FireflyIII\Support\JsonApi\Concerns\UsergroupAware;
use FireflyIII\Support\JsonApi\Enrichments\AccountEnrichment;
use FireflyIII\Support\JsonApi\ExpandsQuery;
use FireflyIII\Support\JsonApi\FiltersPagination;
use FireflyIII\Support\JsonApi\SortsCollection;
use FireflyIII\Support\JsonApi\ValidateSortParameters;
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

    #[\Override]
    public function get(): iterable
    {
        $filters    = $this->queryParameters->filter();
        $sort       = $this->queryParameters->sortFields();
        $pagination = $this->filtersPagination($this->queryParameters->page());
        $needsAll   = $this->validateParams('account', $sort);
        $query      = $this->userGroup->accounts();

        if (!$needsAll) {
            $query = $this->addPagination($query, $pagination);
        }
        $query      = $this->addSortParams($query, $sort);
        $query      = $this->addFilterParams('account', $query, $filters);

        $collection = $query->get(['accounts.*']);

        // enrich data
        $enrichment = new AccountEnrichment();
        $collection = $enrichment->enrich($collection);

        // add filters after the query

        // add sort after the query
        return $this->sortCollection($collection, $sort);
        //        var_dump($filters->value('name'));
        //        exit;
    }
}
