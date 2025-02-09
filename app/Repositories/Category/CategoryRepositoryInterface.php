<?php

/**
 * CategoryRepositoryInterface.php
 * Copyright (c) 2019 james@firefly-iii.org
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
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Interface CategoryRepositoryInterface.
 */
interface CategoryRepositoryInterface
{
    public function categoryEndsWith(string $query, int $limit): Collection;

    public function categoryStartsWith(string $query, int $limit): Collection;

    public function destroy(Category $category): bool;

    /**
     * Delete all categories.
     */
    public function destroyAll(): void;

    /**
     * Find a category or return NULL
     */
    public function find(int $categoryId): ?Category;

    /**
     * Find a category.
     */
    public function findByName(string $name): ?Category;

    public function findCategory(?int $categoryId, ?string $categoryName): ?Category;

    public function firstUseDate(Category $category): ?Carbon;

    public function getAttachments(Category $category): Collection;

    /**
     * Get all categories with ID's.
     */
    public function getByIds(array $categoryIds): Collection;

    /**
     * Returns a list of all the categories belonging to a user.
     */
    public function getCategories(): Collection;

    public function getNoteText(Category $category): ?string;

    /**
     * Return most recent transaction(journal) date or null when never used before.
     */
    public function lastUseDate(Category $category, Collection $accounts): ?Carbon;

    /**
     * Remove notes.
     */
    public function removeNotes(Category $category): void;

    public function searchCategory(string $query, int $limit): Collection;

    public function setUser(null|Authenticatable|User $user): void;
    public function setUserGroup(UserGroup $userGroup): void;

    /**
     * @throws FireflyException
     */
    public function store(array $data): Category;

    public function update(Category $category, array $data): Category;

    public function updateNotes(Category $category, string $notes): void;
}
