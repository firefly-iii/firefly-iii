<?php
/**
 * TransactionCollectorInterface.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface TransactionCollectorInterface
 *
 */
interface TransactionCollectorInterface
{

    /**
     * Add a specific filter.
     *
     * @param string $filter
     *
     * @return TransactionCollectorInterface
     */
    public function addFilter(string $filter): TransactionCollectorInterface;

    /**
     * Get transactions with a specific amount.
     *
     * @param string $amount
     *
     * @return TransactionCollectorInterface
     */
    public function amountIs(string $amount): TransactionCollectorInterface;

    /**
     * Get transactions where the amount is less than.
     *
     * @param string $amount
     *
     * @return TransactionCollectorInterface
     */
    public function amountLess(string $amount): TransactionCollectorInterface;

    /**
     * Get transactions where the amount is more than.
     *
     * @param string $amount
     *
     * @return TransactionCollectorInterface
     */
    public function amountMore(string $amount): TransactionCollectorInterface;

    /**
     * Count the result.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Get a paginated result.
     *
     * @return LengthAwarePaginator
     */
    public function getPaginatedTransactions(): LengthAwarePaginator;

    /**
     * Get the query.
     *
     * @return EloquentBuilder
     */
    public function getQuery(): EloquentBuilder;

    /**
     * Get all transactions.
     *
     * @return Collection
     */
    public function getTransactions(): Collection;

    /**
     * Set to ignore the cache.
     *
     * @return TransactionCollectorInterface
     */
    public function ignoreCache(): TransactionCollectorInterface;

    /**
     * Remove a filter.
     *
     * @param string $filter
     *
     * @return TransactionCollectorInterface
     */
    public function removeFilter(string $filter): TransactionCollectorInterface;

    /**
     * Set the accounts to collect from.
     *
     * @param Collection $accounts
     *
     * @return TransactionCollectorInterface
     */
    public function setAccounts(Collection $accounts): TransactionCollectorInterface;

    /**
     * Collect transactions after a specific date.
     *
     * @param Carbon $after
     *
     * @return TransactionCollectorInterface
     */
    public function setAfter(Carbon $after): TransactionCollectorInterface;

    /**
     * Include all asset accounts.
     *
     * @return TransactionCollectorInterface
     */
    public function setAllAssetAccounts(): TransactionCollectorInterface;

    /**
     * Collect transactions before a specific date.
     *
     * @param Carbon $before
     *
     * @return TransactionCollectorInterface
     */
    public function setBefore(Carbon $before): TransactionCollectorInterface;

    /**
     * Set the bills to filter on.
     *
     * @param Collection $bills
     *
     * @return TransactionCollectorInterface
     */
    public function setBills(Collection $bills): TransactionCollectorInterface;

    /**
     * Set the budget to filter on.
     *
     * @param Budget $budget
     *
     * @return TransactionCollectorInterface
     */
    public function setBudget(Budget $budget): TransactionCollectorInterface;

    /**
     * Set the budgets to filter on.
     *
     * @param Collection $budgets
     *
     * @return TransactionCollectorInterface
     */
    public function setBudgets(Collection $budgets): TransactionCollectorInterface;

    /**
     * Set the categories to filter on.
     *
     * @param Collection $categories
     *
     * @return TransactionCollectorInterface
     */
    public function setCategories(Collection $categories): TransactionCollectorInterface;

    /**
     * Set the category to filter on.
     *
     * @param Category $category
     *
     * @return TransactionCollectorInterface
     */
    public function setCategory(Category $category): TransactionCollectorInterface;

    /**
     * Set the required currency (local or foreign)
     *
     * @param TransactionCurrency $currency
     *
     * @return TransactionCollectorInterface
     */
    public function setCurrency(TransactionCurrency $currency): TransactionCollectorInterface;

    /**
     * Set the journal IDs to filter on.
     *
     * @param array $journalIds
     *
     * @return TransactionCollectorInterface
     */
    public function setJournalIds(array $journalIds): TransactionCollectorInterface;

    /**
     * Set the journals to filter on.
     *
     * @param Collection $journals
     *
     * @return TransactionCollectorInterface
     */
    public function setJournals(Collection $journals): TransactionCollectorInterface;

    /**
     * Set the page limit.
     *
     * @param int $limit
     *
     * @return TransactionCollectorInterface
     */
    public function setLimit(int $limit): TransactionCollectorInterface;

    /**
     * Set the offset.
     *
     * @param int $offset
     *
     * @return TransactionCollectorInterface
     */
    public function setOffset(int $offset): TransactionCollectorInterface;

    /**
     * Set the opposing accounts to collect from.
     *
     * @param Collection $accounts
     *
     * @return TransactionCollectorInterface
     */
    public function setOpposingAccounts(Collection $accounts): TransactionCollectorInterface;

    /**
     * Set the page to get.
     *
     * @param int $page
     *
     * @return TransactionCollectorInterface
     */
    public function setPage(int $page): TransactionCollectorInterface;

    /**
     * Set the date range.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return TransactionCollectorInterface
     */
    public function setRange(Carbon $start, Carbon $end): TransactionCollectorInterface;

    /**
     * Set the tag to collect from.
     *
     * @param Tag $tag
     *
     * @return TransactionCollectorInterface
     */
    public function setTag(Tag $tag): TransactionCollectorInterface;

    /**
     * Set the tags to collect from.
     *
     * @param Collection $tags
     *
     * @return TransactionCollectorInterface
     */
    public function setTags(Collection $tags): TransactionCollectorInterface;

    /**
     * Set the types to collect.
     *
     * @param array $types
     *
     * @return TransactionCollectorInterface
     */
    public function setTypes(array $types): TransactionCollectorInterface;

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
     * @return TransactionCollectorInterface
     */
    public function withBudgetInformation(): TransactionCollectorInterface;

    /**
     * Include category information.
     *
     * @return TransactionCollectorInterface
     */
    public function withCategoryInformation(): TransactionCollectorInterface;

    /**
     * Include opposing account information.
     *
     * @return TransactionCollectorInterface
     */
    public function withOpposingAccount(): TransactionCollectorInterface;

    /**
     * Include tranactions without a budget.
     *
     * @return TransactionCollectorInterface
     */
    public function withoutBudget(): TransactionCollectorInterface;

    /**
     * Include tranactions without a category.
     *
     * @return TransactionCollectorInterface
     */
    public function withoutCategory(): TransactionCollectorInterface;
}
