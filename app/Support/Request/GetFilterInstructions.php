<?php

/*
 * GetFilterInstructions.php
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

trait GetFilterInstructions
{
    private const string INVALID_FILTER = '%INVALID_JAMES_%';

    final public function getFilterInstructions(string $key): array
    {
        $config  = config(sprintf('firefly.filters.allowed.%s', $key));
        $allowed = array_keys($config);
        $set     = $this->get('filters', []);
        $result  = [];
        if (0 === count($set)) {
            return [];
        }
        foreach ($set as $info) {
            $column      = $info['column'] ?? 'NOPE';
            $filterValue = (string) ($info['filter'] ?? self::INVALID_FILTER);
            if (false === in_array($column, $allowed, true)) {
                // skip invalid column
                continue;
            }
            $filterType = $config[$column] ?? false;

            switch ($filterType) {
                default:
                    exit(sprintf('Do not support filter type "%s"', $filterType));

                case 'boolean':
                    $filterValue = $this->booleanInstruction($filterValue);

                    break;

                case 'string':
                    break;
            }
            $result[$column] = $filterValue;
        }

        return $result;
    }

    public function booleanInstruction(string $filterValue): ?bool
    {
        if ('true' === $filterValue) {
            return true;
        }
        if ('false' === $filterValue) {
            return false;
        }

        return null;
    }
}
