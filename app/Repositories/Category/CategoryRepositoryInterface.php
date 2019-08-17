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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
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
     * @param int|null      $categoryId
     * @param string|null   $categoryName
     *
     * @return Category|null
     */
    public function findCategory( ?int $categoryId, ?string $categoryName): ?Category;

    /**
     * @param Category $category
     *
     * @return bool
     */
    public function destroy(Category $category): bool;

    /**
     * Returns the amount earned in a category, for a set of accounts, in a specific period.
     *
     * @param Category $category
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function earnedInPeriod(Category $category, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * A very cryptic method name that means:
     *
     * Get me the amount earned in this period, grouped per currency, where no category was set.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function earnedInPeriodPcWoCategory(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function earnedInPeriodPerCurrency(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * Find a category.
     *
     * @param string $name
     *
     * @return Category
     */
    public function findByName(string $name): ?Category;

    /**
     * Find a category or return NULL
     *
     * @param int $categoryId
     *
     * @return Category|null
     */
    public function findNull(int $categoryId): ?Category;

    /**
     * @param Category $category
     *
     * @return Carbon|null
     */
    public function firstUseDate(Category $category): ?Carbon;

    /**
     * Get all categories with ID's.
     *
     * @param array $categoryIds
     *
     * @return Collection
     */
    public function getByIds(array $categoryIds): Collection;

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
     * TODO not multi-currency
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodExpenses(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array;



    /**
     * TODO not multi-currency
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodExpensesNoCategory(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * TODO not multi-currency
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodIncome(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * TODO not multi-currency
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodIncomeNoCategory(Collection $accounts, Carbon $start, Carbon $end): array;



    /**
     * @param string $query
     *
     * @return Collection
     */
    public function searchCategory(string $query): Collection;



    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * Returns the amount spent in a category, for a set of accounts, in a specific period.
     *
     * @param Category $category
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentInPeriod(Category $category, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * A very cryptic method name that means:
     *
     * Get me the amount spent in this period, grouped per currency, where no category was set.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentInPeriodPcWoCategory(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentInPeriodPerCurrency(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array;

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
