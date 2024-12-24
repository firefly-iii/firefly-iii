<?php

/**
 * BasicDataSupport.php
 * Copyright (c) 2019 james@firefly-iii.org
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Http\Controllers;

use Carbon\Carbon;

/**
 * Trait BasicDataSupport
 */
trait BasicDataSupport
{
    /**
     * Find the ID in a given array. Return '0' if not there (amount).
     *
     * @return null|mixed
     */
    protected function isInArray(array $array, int $entryId)
    {
        $key = $this->convertToNative ? 'native_balance' : 'balance';
        return $array[$entryId][$key] ?? '0';
    }

    /**
     * Find the ID in a given array. Return null if not there (amount).
     */
    protected function isInArrayDate(array $array, int $entryId): ?Carbon
    {
        return $array[$entryId] ?? null;
    }
}
