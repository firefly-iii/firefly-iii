<?php
/**
 * CategoryFactory.php
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

namespace FireflyIII\Factory;


use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\User;
use Log;

/**
 * Class CategoryFactory
 */
class CategoryFactory
{
    /** @var CategoryRepositoryInterface */
    private $repository;
    /** @var User */
    private $user;

    /**
     * CategoryFactory constructor.
     */
    public function __construct()
    {
        $this->repository = app(CategoryRepositoryInterface::class);
    }

    /**
     * @param int|null    $categoryId
     * @param null|string $categoryName
     *
     * @return Category|null
     */
    public function findOrCreate(?int $categoryId, ?string $categoryName): ?Category
    {
        $categoryId   = intval($categoryId);
        $categoryName = strval($categoryName);

        Log::debug(sprintf('Going to find category with ID %d and name "%s"', $categoryId, $categoryName));

        if (strlen($categoryName) === 0 && $categoryId === 0) {
            return null;
        }
        // first by ID:
        if ($categoryId > 0) {
            $category = $this->repository->findNull($categoryId);
            if (!is_null($category)) {
                return $category;
            }
        }

        if (strlen($categoryName) > 0) {
            $category = $this->repository->findByName($categoryName);
            if (!is_null($category)) {
                return $category;
            }

            return Category::create(
                [
                    'user_id' => $this->user->id,
                    'name'    => $categoryName,
                ]
            );
        }

        return null;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        $this->repository->setUser($user);
    }

}