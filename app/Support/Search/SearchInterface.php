<?php
/**
 * SearchInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Search;

use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface SearchInterface
 *
 * @package FireflyIII\Support\Search
 */
interface SearchInterface
{
    /**
     * @return string
     */
    public function getWordsAsString(): string;

    /**
     * @return bool
     */
    public function hasModifiers(): bool;

    /**
     * @param string $query
     */
    public function parseQuery(string $query);


    /**
     * @return Collection
     */
    public function searchTransactions(): Collection;

    /**
     * @param int $limit
     */
    public function setLimit(int $limit);

    /**
     * @param User $user
     */
    public function setUser(User $user);
}
