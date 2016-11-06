<?php
/**
 * CategoryRepositoryInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Repositories\Category;

use Carbon\Carbon;
use FireflyIII\Models\Category;
use Illuminate\Support\Collection;

/**
 * Interface CategoryRepositoryInterface
 *
 * @package FireflyIII\Repositories\Category
 */
interface CategoryRepositoryInterface
{

    /**
     * @param Category $category
     *
     * @return bool
     */
    public function destroy(Category $category): bool;

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function earnedInPeriod(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): string;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function earnedInPeriodWithoutCategory(Collection $accounts, Carbon $start, Carbon $end) :string;

    /**
     * Find a category
     *
     * @param int $categoryId
     *
     * @return Category
     */
    public function find(int $categoryId) : Category;

    /**
     * Find a category
     *
     * @param string $name
     *
     * @return Category
     */
    public function findByName(string $name) : Category;

    /**
     * @param Category $category
     *
     * @return Carbon
     */
    public function firstUseDate(Category $category): Carbon;

    /**
     * Returns a list of all the categories belonging to a user.
     *
     * @return Collection
     */
    public function getCategories(): Collection;

    /**
     * Return most recent transaction(journal) date.
     *
     * @param Category   $category
     * @param Collection $accounts
     *
     * @return Carbon
     */
    public function lastUseDate(Category $category, Collection $accounts): Carbon;

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriod(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): string;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriodWithoutCategory(Collection $accounts, Carbon $start, Carbon $end) : string;

    /**
     * @param array $data
     *
     * @return Category
     */
    public function store(array $data): Category;

    /**
     * @param Category $category
     * @param array    $data
     *
     * @return Category
     */
    public function update(Category $category, array $data): Category;
}
