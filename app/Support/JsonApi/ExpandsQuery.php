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

use FireflyIII\Models\Account;
use FireflyIII\Support\Http\Api\AccountFilter;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Core\Query\FilterParameters;
use LaravelJsonApi\Core\Query\SortFields;

trait ExpandsQuery
{
    use AccountFilter;

    final protected function addFilterParams(string $class, Builder $query, ?FilterParameters $filters): Builder
    {
        Log::debug(__METHOD__);
        if (null === $filters) {
            return $query;
        }
        if (0 === count($filters->all())) {
            return $query;
        }
        // parse filters valid for this class.
        $parsed = $this->parseAllFilters($class, $filters);

        // expand query for each query filter
        $config = config('api.valid_query_filters')[$class];
        $query->where(function (Builder $q) use ($config, $parsed): void {
            foreach ($parsed as $key => $filter) {
                if (in_array($key, $config, true)) {
                    Log::debug(sprintf('Add query filter "%s"', $key));
                    // add type to query:
                    foreach ($filter as $value) {
                        $q->whereLike($key, sprintf('%%%s%%', $value));
                    }
                }
            }
        });

        // TODO this is special treatment, but alas, unavoidable right now.
        if (Account::class === $class && array_key_exists('type', $parsed)) {
            if (count($parsed['type']) > 0) {
                $query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
                $query->whereIn('account_types.type', $parsed['type']);
            }
        }

        return $query;
    }

    private function parseAllFilters(string $class, FilterParameters $filters): array
    {
        $config = config('api.valid_api_filters')[$class];
        $parsed = [];
        foreach ($filters->all() as $filter) {
            $key = $filter->key();
            if (!in_array($key, $config, true)) {
                continue;
            }
            // make array if not array:
            $value = $filter->value();
            if (null === $value) {
                continue;
            }
            if (!is_array($value)) {
                $value = [$value];
            }

            switch ($filter->key()) {
                case 'name':
                    $parsed['name'] = $value;

                    break;

                case 'type':
                    $parsed['type'] = $this->parseAccountTypeFilter($value);

                    break;
            }
        }

        return $parsed;
    }

    private function parseAccountTypeFilter(array $value): array
    {
        $return = [];
        foreach ($value as $entry) {
            $return = array_merge($return, $this->mapAccountTypes($entry));
        }

        return array_unique($return);
    }

    final protected function addPagination(Builder $query, array $pagination): Builder
    {
        $skip = ($pagination['number'] - 1) * $pagination['size'];

        return $query->skip($skip)->take($pagination['size']);
    }

    final protected function addSortParams(string $class, Builder $query, ?SortFields $sort): Builder
    {
        $config = config('api.valid_query_sort')[$class] ?? [];
        if (null === $sort) {
            return $query;
        }
        foreach ($sort->all() as $sortField) {
            if (in_array($sortField->name(), $config, true)) {
                $query->orderBy($sortField->name(), $sortField->isAscending() ? 'ASC' : 'DESC');
            }
        }

        return $query;
    }
}
