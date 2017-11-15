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
 * Interface JournalCollectorInterface.
 */
interface JournalCollectorInterface
{
    /**
     * @param string $filter
     *
     * @return JournalCollectorInterface
     */
    public function addFilter(string $filter): self;

    /**
     * @param string $amount
     *
     * @return JournalCollectorInterface
     */
    public function amountIs(string $amount): self;

    /**
     * @param string $amount
     *
     * @return JournalCollectorInterface
     */
    public function amountLess(string $amount): self;

    /**
     * @param string $amount
     *
     * @return JournalCollectorInterface
     */
    public function amountMore(string $amount): self;

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
    public function removeFilter(string $filter): self;

    /**
     * @param Collection $accounts
     *
     * @return JournalCollectorInterface
     */
    public function setAccounts(Collection $accounts): self;

    /**
     * @param Carbon $after
     *
     * @return JournalCollectorInterface
     */
    public function setAfter(Carbon $after): self;

    /**
     * @return JournalCollectorInterface
     */
    public function setAllAssetAccounts(): self;

    /**
     * @param Carbon $before
     *
     * @return JournalCollectorInterface
     */
    public function setBefore(Carbon $before): self;

    /**
     * @param Collection $bills
     *
     * @return JournalCollectorInterface
     */
    public function setBills(Collection $bills): self;

    /**
     * @param Budget $budget
     *
     * @return JournalCollectorInterface
     */
    public function setBudget(Budget $budget): self;

    /**
     * @param Collection $budgets
     *
     * @return JournalCollectorInterface
     */
    public function setBudgets(Collection $budgets): self;

    /**
     * @param Collection $categories
     *
     * @return JournalCollectorInterface
     */
    public function setCategories(Collection $categories): self;

    /**
     * @param Category $category
     *
     * @return JournalCollectorInterface
     */
    public function setCategory(Category $category): self;

    /**
     * @param int $limit
     *
     * @return JournalCollectorInterface
     */
    public function setLimit(int $limit): self;

    /**
     * @param int $offset
     *
     * @return JournalCollectorInterface
     */
    public function setOffset(int $offset): self;

    /**
     * @param int $page
     *
     * @return JournalCollectorInterface
     */
    public function setPage(int $page): self;

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return JournalCollectorInterface
     */
    public function setRange(Carbon $start, Carbon $end): self;

    /**
     * @param Tag $tag
     *
     * @return JournalCollectorInterface
     */
    public function setTag(Tag $tag): self;

    /**
     * @param Collection $tags
     *
     * @return JournalCollectorInterface
     */
    public function setTags(Collection $tags): self;

    /**
     * @param array $types
     *
     * @return JournalCollectorInterface
     */
    public function setTypes(array $types): self;

    public function setUser(User $user);

    /**
     *
     */
    public function startQuery();

    /**
     * @return JournalCollectorInterface
     */
    public function withBudgetInformation(): self;

    /**
     * @return JournalCollectorInterface
     */
    public function withCategoryInformation(): self;

    /**
     * @return JournalCollectorInterface
     */
    public function withOpposingAccount(): self;

    /**
     * @return JournalCollectorInterface
     */
    public function withoutBudget(): self;

    /**
     * @return JournalCollectorInterface
     */
    public function withoutCategory(): self;
}
