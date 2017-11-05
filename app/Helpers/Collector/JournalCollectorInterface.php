<?php
/**
 * JournalCollectorInterface.php
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

namespace FireflyIII\Helpers\Collector;

use Carbon\Carbon;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface JournalCollectorInterface
 *
 * @package FireflyIII\Helpers\Collector
 */
interface JournalCollectorInterface
{
    /**
     * @param string $filter
     *
     * @return JournalCollectorInterface
     */
    public function addFilter(string $filter): JournalCollectorInterface;

    /**
     * @param string $amount
     *
     * @return JournalCollectorInterface
     */
    public function amountIs(string $amount): JournalCollectorInterface;

    /**
     * @param string $amount
     *
     * @return JournalCollectorInterface
     */
    public function amountLess(string $amount): JournalCollectorInterface;

    /**
     * @param string $amount
     *
     * @return JournalCollectorInterface
     */
    public function amountMore(string $amount): JournalCollectorInterface;

    /**
     * @return int
     */
    public function count(): int;

    /**
     * @return Collection
     */
    public function getJournals(): Collection;

    /**
     * @return LengthAwarePaginator
     */
    public function getPaginatedJournals(): LengthAwarePaginator;

    /**
     * @param string $filter
     *
     * @return JournalCollectorInterface
     */
    public function removeFilter(string $filter): JournalCollectorInterface;

    /**
     * @param Collection $accounts
     *
     * @return JournalCollectorInterface
     */
    public function setAccounts(Collection $accounts): JournalCollectorInterface;

    /**
     * @param Carbon $after
     *
     * @return JournalCollectorInterface
     */
    public function setAfter(Carbon $after): JournalCollectorInterface;

    /**
     * @return JournalCollectorInterface
     */
    public function setAllAssetAccounts(): JournalCollectorInterface;

    /**
     * @param Carbon $before
     *
     * @return JournalCollectorInterface
     */
    public function setBefore(Carbon $before): JournalCollectorInterface;

    /**
     * @param Collection $bills
     *
     * @return JournalCollectorInterface
     */
    public function setBills(Collection $bills): JournalCollectorInterface;

    /**
     * @param Budget $budget
     *
     * @return JournalCollectorInterface
     */
    public function setBudget(Budget $budget): JournalCollectorInterface;

    /**
     * @param Collection $budgets
     *
     * @return JournalCollectorInterface
     */
    public function setBudgets(Collection $budgets): JournalCollectorInterface;

    /**
     * @param Collection $categories
     *
     * @return JournalCollectorInterface
     */
    public function setCategories(Collection $categories): JournalCollectorInterface;

    /**
     * @param Category $category
     *
     * @return JournalCollectorInterface
     */
    public function setCategory(Category $category): JournalCollectorInterface;

    /**
     * @param int $limit
     *
     * @return JournalCollectorInterface
     */
    public function setLimit(int $limit): JournalCollectorInterface;

    /**
     * @param int $offset
     *
     * @return JournalCollectorInterface
     */
    public function setOffset(int $offset): JournalCollectorInterface;

    /**
     * @param int $page
     *
     * @return JournalCollectorInterface
     */
    public function setPage(int $page): JournalCollectorInterface;

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return JournalCollectorInterface
     */
    public function setRange(Carbon $start, Carbon $end): JournalCollectorInterface;

    /**
     * @param Tag $tag
     *
     * @return JournalCollectorInterface
     */
    public function setTag(Tag $tag): JournalCollectorInterface;

    /**
     * @param Collection $tags
     *
     * @return JournalCollectorInterface
     */
    public function setTags(Collection $tags): JournalCollectorInterface;

    /**
     * @param array $types
     *
     * @return JournalCollectorInterface
     */
    public function setTypes(array $types): JournalCollectorInterface;

    public function setUser(User $user);

    /**
     *
     */
    public function startQuery();

    /**
     * @return JournalCollectorInterface
     */
    public function withBudgetInformation(): JournalCollectorInterface;

    /**
     * @return JournalCollectorInterface
     */
    public function withCategoryInformation(): JournalCollectorInterface;

    /**
     * @return JournalCollectorInterface
     */
    public function withOpposingAccount(): JournalCollectorInterface;

    /**
     * @return JournalCollectorInterface
     */
    public function withoutBudget(): JournalCollectorInterface;

    /**
     * @return JournalCollectorInterface
     */
    public function withoutCategory(): JournalCollectorInterface;
}
