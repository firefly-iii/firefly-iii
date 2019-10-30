<?php
/**
 * CategoryRepositoryInterface.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\Category;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Category;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface CategoryRepositoryInterface.
 */
interface CategoryRepositoryInterface
{
    /**
     * Delete all categories.
     */
    public function destroyAll(): void;

    /**
     * @param Category $category
     *
     * @return bool
     */
    public function destroy(Category $category): bool;

    /**
     * Find a category.
     *
     * @param string $name
     *
     * @return Category
     */
    public function findByName(string $name): ?Category;

    /**
     * @param int|null    $categoryId
     * @param string|null $categoryName
     *
     * @return Category|null
     */
    public function findCategory(?int $categoryId, ?string $categoryName): ?Category;

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
     * @param array $data
     * @throws FireflyException
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
