<?php

/*
 * FiltersPagination.php
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

trait FiltersPagination
{
    protected function filtersPagination(?array $pagination): array
    {
        if (null === $pagination) {
            return [
                'number' => 1,
                'size'   => $this->getPageSize(),
            ];
        }
        // cleanup page number
        $pagination['number'] = (int) ($pagination['number'] ?? 1);
        $pagination['number'] = min(65536, max($pagination['number'], 1));

        // clean up page size
        $pagination['size'] = (int) ($pagination['size'] ?? $this->getPageSize());
        $pagination['size'] = min(1337, max($pagination['size'], 1));

        return $pagination;
    }

    private function getPageSize(): int
    {
        if (auth()->check()) {
            return (int) app('preferences')->get('listPageSize', 50)->data;
        }

        return 50;
    }
}
