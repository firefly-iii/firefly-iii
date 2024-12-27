<?php

/*
 * ParsesQueryFilters.php
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

namespace FireflyIII\Support\Http\Api;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Core\Query\QueryParameters;

trait ParsesQueryFilters
{
    private function arrayOfStrings(QueryParameters $parameters, string $field): array
    {
        $array = $parameters->filter()?->value($field, []) ?? [];

        return is_string($array) ? [$array] : $array;
    }

    private function dateOrToday(QueryParameters $parameters, string $field): Carbon
    {
        $date  = today();

        $value = $parameters->filter()?->value($field, date('Y-m-d'));

        if (is_array($value)) {
            Log::error(sprintf('Multiple values for date field "%s". Using first value.', $field));
            $value = $value[0];
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value, config('app.timezone'));
        } catch (InvalidFormatException $e) {
            Log::debug(sprintf('Invalid date format in request. Using today: %s', $e->getMessage()));
        }

        return $date;
    }

    private function integerFromQueryParams(QueryParameters $parameters, string $field, int $default): int
    {
        return (int) ($parameters->page()[$field] ?? $default);
    }

    private function stringFromFilterParams(QueryParameters $parameters, string $field, string $default): string
    {
        return (string) $parameters->filter()?->value($field, $default) ?? $default;
    }

    private function stringFromQueryParams(QueryParameters $parameters, string $field, string $default): string
    {
        return (string) ($parameters->page()[$field] ?? $default);
    }
}
