<?php
/*
 * ExpandsQuery.php
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

namespace FireflyIII\Support\JsonApi;

use FireflyIII\Support\Http\Api\AccountFilter;
use Illuminate\Contracts\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Query\FilterParameters;
use LaravelJsonApi\Core\Query\SortFields;

trait ExpandsQuery
{
    use AccountFilter;

    final protected function addPagination(Builder $query, array $pagination): Builder
    {
        $skip = ($pagination['number'] - 1) * $pagination['size'];

        return $query->skip($skip)->take($pagination['size']);
    }

    final protected function addSortParams(Builder $query, ?SortFields $sort): Builder
    {
        if (null === $sort) {
            return $query;
        }
        foreach ($sort->all() as $sortField) {
            $query->orderBy($sortField->name(), $sortField->isAscending() ? 'ASC' : 'DESC');
        }

        return $query;
    }

    final protected function addFilterParams(string $class, Builder $query, ?FilterParameters $filters): Builder
    {
        if (null === $filters) {
            return $query;
        }
        $config      = config(sprintf('firefly.valid_query_filters.%s', $class)) ?? [];
        if (0 === count($filters->all())) {
            return $query;
        }
        $query->where(function (Builder $q) use ($config, $filters): void {
            foreach ($filters->all() as $filter) {
                if (in_array($filter->key(), $config, true)) {
                    foreach ($filter->value() as $value) {
                        $q->where($filter->key(), 'LIKE', sprintf('%%%s%%', $value));
                    }
                }
            }
        });

        // some filters are special, i.e. the account type filter.
        $typeFilters = $filters->value('type', false);
        if (false !== $typeFilters && count($typeFilters) > 0) {
            $query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            foreach ($typeFilters as $typeFilter) {
                $types = $this->mapAccountTypes($typeFilter);
                $query->whereIn('account_types.type', $types);
            }
        }

        return $query;
    }
}
