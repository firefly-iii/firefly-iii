<?php
/**
 * JournalCollectorInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Helpers\Collector;

use Carbon\Carbon;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
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
     * @return int
     */
    public function count(): int;

    /**
     * @return JournalCollectorInterface
     */
    public function disableFilter(): JournalCollectorInterface;

    /**
     * @return Collection
     */
    public function getJournals(): Collection;

    /**
     * @return LengthAwarePaginator
     */
    public function getPaginatedJournals():LengthAwarePaginator;

    /**
     * @param Collection $accounts
     *
     * @return JournalCollectorInterface
     */
    public function setAccounts(Collection $accounts): JournalCollectorInterface;

    /**
     * @return JournalCollectorInterface
     */
    public function setAllAssetAccounts(): JournalCollectorInterface;

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
     * @param array $types
     *
     * @return JournalCollectorInterface
     */
    public function setTypes(array $types): JournalCollectorInterface;

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