<?php

/**
 * SearchInterface.php
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

namespace FireflyIII\Support\Search;

use Carbon\Carbon;
use FireflyIII\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface SearchInterface.
 */
interface SearchInterface
{
    public function getInvalidOperators(): array;

    public function getModifiers(): Collection;

    public function getOperators(): Collection;

    public function getWords(): array;

    public function getWordsAsString(): string;

    public function getExcludedWords(): array;

    public function hasModifiers(): bool;

    public function parseQuery(string $query): void;

    public function searchTime(): float;

    public function searchTransactions(): LengthAwarePaginator;

    public function setDate(Carbon $date): void;

    public function setLimit(int $limit): void;

    public function setPage(int $page): void;

    public function setUser(User $user): void;
}
