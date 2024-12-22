<?php

/*
 * GetSortInstructions.php
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

namespace FireflyIII\Support\Request;

trait GetSortInstructions
{
    final public function getSortInstructions(string $key): array
    {
        $allowed = config(sprintf('firefly.sorting.allowed.%s', $key));
        $set     = $this->get('sorting', []);
        $result  = [];
        if (0 === count($set)) {
            return [];
        }
        foreach ($set as $info) {
            $column          = $info['column'] ?? 'NOPE';
            $direction       = $info['direction'] ?? 'NOPE';
            if ('asc' !== $direction && 'desc' !== $direction) {
                // skip invalid direction
                continue;
            }
            if (false === in_array($column, $allowed, true)) {
                // skip invalid column
                continue;
            }
            $result[$column] = $direction;
        }

        return $result;
    }
}
