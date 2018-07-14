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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Helpers\Collector;

use Carbon\Carbon;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface JournalCollectorInterface.
 */
interface JournalCollectorInterface
{

    /**
     * Add a specific filter.
     *
     * @param string $filter
     *
     * @return JournalCollectorInterface
     */
    public function addFilter(string $filter): JournalCollectorInterface;

    /**
     * Get transactions with a specific amount.
     *
     * @param string $amount
     *
     * @return JournalCollectorInterface
     */
    public function amountIs(string $amount): JournalCollectorInterface;

    /**
     * Get transactions where the amount is less than.
     *
     * @param string $amount
     *
     * @return JournalCollectorInterface
     */
    public function amountLess(string $amount): JournalCollectorInterface;

    /**
     * Get transactions where the amount is more than.
     *
     * @param string $amount
     *
     * @return JournalCollectorInterface
     */
    public function amountMore(string $amount): JournalCollectorInterface;

    /**
     * Count the result.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Get all journals.
     * TODO rename me.
     *
     * @return Collection
     */
    public function getJournals(): Collection;

    /**
     * Get a paginated result.
     *
     * @return LengthAwarePaginator
     */
    public function getPaginatedJournals(): LengthAwarePaginator;

    /**
     * Get the query.
     *
     * @return EloquentBuilder
     */
    public function getQuery(): EloquentBuilder;

    /**
     * Set to ignore the cache.
     *
     * @return JournalCollectorInterface
     */
    public function ignoreCache(): JournalCollectorInterface;

    /**
     * Remove a filter.
     *
     * @param string $filter
     *
     * @return JournalCollectorInterface
     */
    public function removeFilter(string $filter): JournalCollectorInterface;

    /**
     * Set the accounts to collect from.
     *
     * @param Collection $accounts
     *
     * @return JournalCollectorInterface
     */
    public function setAccounts(Collection $accounts): JournalCollectorInterface;

    /**
     * Collect journals after a specific date.
     *
     * @param Carbon $after
     *
     * @return JournalCollectorInterface
     */
    public function setAfter(Carbon $after): JournalCollectorInterface;

    /**
     * Include all asset accounts.
     *
     * @return JournalCollectorInterface
     */
    public function setAllAssetAccounts(): JournalCollectorInterface;

    /**
     * Collect journals before a specific date.
     *
     * @param Carbon $before
     *
     * @return JournalCollectorInterface
     */
    public function setBefore(Carbon $before): JournalCollectorInterface;

    /**
     * Set the bills to filter on.
     *
     * @param Collection $bills
     *
     * @return JournalCollectorInterface
     */
    public function setBills(Collection $bills): JournalCollectorInterface;

    /**
     * Set the budget to filter on.
     *
     * @param Budget $budget
     *
     * @return JournalCollectorInterface
     */
    public function setBudget(Budget $budget): JournalCollectorInterface;

    /**
     * Set the budgets to filter on.
     *
     * @param Collection $budgets
     *
     * @return JournalCollectorInterface
     */
    public function setBudgets(Collection $budgets): JournalCollectorInterface;

    /**
     * Set the categories to filter on.
     *
     * @param Collection $categories
     *
     * @return JournalCollectorInterface
     */
    public function setCategories(Collection $categories): JournalCollectorInterface;

    /**
     * Set the category to filter on.
     *
     * @param Category $category
     *
     * @return JournalCollectorInterface
     */
    public function setCategory(Category $category): JournalCollectorInterface;

    /**
     * Set the journals to filter on.
     *
     * @param Collection $journals
     *
     * @return JournalCollectorInterface
     */
    public function setJournals(Collection $journals): JournalCollectorInterface;

    /**
     * Set the page limit.
     *
     * @param int $limit
     *
     * @return JournalCollectorInterface
     */
    public function setLimit(int $limit): JournalCollectorInterface;

    /**
     * Set the offset.
     *
     * @param int $offset
     *
     * @return JournalCollectorInterface
     */
    public function setOffset(int $offset): JournalCollectorInterface;

    /**
     * Set the opposing accounts to collect from.
     *
     * @param Collection $accounts
     *
     * @return JournalCollectorInterface
     */
    public function setOpposingAccounts(Collection $accounts): JournalCollectorInterface;

    /**
     * Set the page to get.
     *
     * @param int $page
     *
     * @return JournalCollectorInterface
     */
    public function setPage(int $page): JournalCollectorInterface;

    /**
     * Set the date range.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return JournalCollectorInterface
     */
    public function setRange(Carbon $start, Carbon $end): JournalCollectorInterface;

    /**
     * Set the tag to collect from.
     *
     * @param Tag $tag
     *
     * @return JournalCollectorInterface
     */
    public function setTag(Tag $tag): JournalCollectorInterface;

    /**
     * Set the tags to collect from.
     *
     * @param Collection $tags
     *
     * @return JournalCollectorInterface
     */
    public function setTags(Collection $tags): JournalCollectorInterface;

    /**
     * Set the types to collect.
     *
     * @param array $types
     *
     * @return JournalCollectorInterface
     */
    public function setTypes(array $types): JournalCollectorInterface;

    /**
     * Set the user.
     *
     * @param User $user
     *
     * @return mixed
     */
    public function setUser(User $user);

    /**
     * Start the query.
     */
    public function startQuery();

    /**
     * Include budget information.
     *
     * @return JournalCollectorInterface
     */
    public function withBudgetInformation(): JournalCollectorInterface;

    /**
     * Include category information.
     *
     * @return JournalCollectorInterface
     */
    public function withCategoryInformation(): JournalCollectorInterface;

    /**
     * Include opposing account information.
     *
     * @return JournalCollectorInterface
     */
    public function withOpposingAccount(): JournalCollectorInterface;

    /**
     * Include tranactions without a budget.
     *
     * @return JournalCollectorInterface
     */
    public function withoutBudget(): JournalCollectorInterface;

    /**
     * Include tranactions without a category.
     *
     * @return JournalCollectorInterface
     */
    public function withoutCategory(): JournalCollectorInterface;
}
