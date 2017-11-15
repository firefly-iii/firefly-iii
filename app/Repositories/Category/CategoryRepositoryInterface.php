<?php
/**
 * CategoryRepositoryInterface.php
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

namespace FireflyIII\Repositories\Category;

use Carbon\Carbon;
use FireflyIII\Models\Category;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface CategoryRepositoryInterface.
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
     * Find a category.
     *
     * @param int $categoryId
     *
     * @return Category
     */
    public function find(int $categoryId): Category;

    /**
     * Find a category.
     *
     * @param string $name
     *
     * @return Category
     */
    public function findByName(string $name): Category;

    /**
     * @param Category $category
     *
     * @return Carbon|null
     */
    public function firstUseDate(Category $category): ?Carbon;

    /**
     * Returns a list of all the categories belonging to a user.
     *
     * @return Collection
     */
    public function getCategories(): Collection;

    /**
     * Return most recent transaction(journal) date or null when never used before.
     *
     * @param Category   $category
     * @param Collection $accounts
     *
     * @return Carbon|null
     */
    public function lastUseDate(Category $category, Collection $accounts): ?Carbon;

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodExpenses(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodExpensesNoCategory(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodIncome(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodIncomeNoCategory(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param User $user
     */
    public function setUser(User $user);

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
    public function spentInPeriodWithoutCategory(Collection $accounts, Carbon $start, Carbon $end): string;

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
