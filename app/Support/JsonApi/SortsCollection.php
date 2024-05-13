<?php
/*
 * SortsCollection.php
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

use Illuminate\Support\Collection;
use LaravelJsonApi\Core\Query\SortFields;

trait SortsCollection
{
    protected function sortCollection(Collection $collection, ?SortFields $sortFields): Collection
    {
        if (null === $sortFields) {
            return $collection;
        }
        foreach ($sortFields->all() as $sortField) {
            $collection = $sortField->isAscending() ? $collection->sortBy($sortField->name()) : $collection->sortByDesc($sortField->name());
        }

        return $collection;
    }
}
