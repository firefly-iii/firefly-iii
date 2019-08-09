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
/** @noinspection MultipleReturnStatementsInspection */
declare(strict_types=1);

namespace FireflyIII\Factory;


use FireflyIII\Models\Category;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class CategoryFactory
 */
class CategoryFactory
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param string $name
     *
     * @return Category|null
     */
    public function findByName(string $name): ?Category
    {
        $result = null;
        /** @var Collection $collection */
        $collection = $this->user->categories()->get();

        // TODO no longer need to loop like this

        /** @var Category $category */
        foreach ($collection as $category) {
            if ($category->name === $name) {
                $result = $category;
                break;
            }
        }

        return $result;
    }

    /**
     * @param int|null    $categoryId
     * @param null|string $categoryName
     *
     * @return Category|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function findOrCreate(?int $categoryId, ?string $categoryName): ?Category
    {
        $categoryId   = (int)$categoryId;
        $categoryName = (string)$categoryName;

        Log::debug(sprintf('Going to find category with ID %d and name "%s"', $categoryId, $categoryName));

        if ('' === $categoryName && 0 === $categoryId) {
            return null;
        }
        // first by ID:
        if ($categoryId > 0) {
            /** @var Category $category */
            $category = $this->user->categories()->find($categoryId);
            if (null !== $category) {
                return $category;
            }
        }

        if ('' !== $categoryName) {
            $category = $this->findByName($categoryName);
            if (null !== $category) {
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
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}
