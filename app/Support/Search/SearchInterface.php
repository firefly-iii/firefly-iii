<?php
/**
 * SearchInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
    public function searchAccounts(): Collection;

    /**
     * @return Collection
     */
    public function searchBudgets(): Collection;

    /**
     * @return Collection
     */
    public function searchCategories(): Collection;

    /**
     * @return Collection
     */
    public function searchTags(): Collection;

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
