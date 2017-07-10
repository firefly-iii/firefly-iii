<?php
/**
 * ImportCategory.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Object;


use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

class ImportCategory
{
    /** @var Category */
    private $category;
    /** @var array */
    private $id = [];
    /** @var array */
    private $name = [];
    /** @var CategoryRepositoryInterface */
    private $repository;
    /** @var  User */
    private $user;

    /**
     * ImportCategory constructor.
     */
    public function __construct()
    {
        $this->category   = new Category();
        $this->repository = app(CategoryRepositoryInterface::class);
        Log::debug('Created ImportCategory.');
    }

    /**
     * @return Category
     */
    public function getCategory(): Category
    {
        if (is_null($this->category->id)) {
            $this->store();
        }

        return $this->category;
    }

    /**
     * @param array $id
     */
    public function setId(array $id)
    {
        $this->id = $id;
    }

    /**
     * @param array $name
     */
    public function setName(array $name)
    {
        $this->name = $name;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        $this->repository->setUser($user);
    }

    /**
     * @return Category
     */
    private function findExistingObject(): Category
    {
        Log::debug('In findExistingObject() for Category');
        // 1: find by ID, or name

        if (count($this->id) === 3) {
            Log::debug(sprintf('Finding category with ID #%d', $this->id['value']));
            /** @var Category $category */
            $category = $this->repository->find(intval($this->id['value']));
            if (!is_null($category->id)) {
                Log::debug(sprintf('Found unmapped category by ID (#%d): %s', $category->id, $category->name));

                return $category;
            }
            Log::debug('Found nothing.');
        }
        // 2: find by name
        if (count($this->name) === 3) {
            /** @var Collection $categories */
            $categories = $this->repository->getCategories();
            $name       = $this->name['value'];
            Log::debug(sprintf('Finding category with name %s', $name));
            $filtered = $categories->filter(
                function (Category $category) use ($name) {
                    if ($category->name === $name) {
                        Log::debug(sprintf('Found unmapped category by name (#%d): %s', $category->id, $category->name));

                        return $category;
                    }

                    return null;
                }
            );

            if ($filtered->count() === 1) {
                return $filtered->first();
            }
            Log::debug('Found nothing.');
        }

        // 4: do not search by account number.
        Log::debug('Found NO existing categories.');

        return new Category;

    }

    /**
     * @return Category
     */
    private function findMappedObject(): Category
    {
        Log::debug('In findMappedObject() for Category');
        $fields = ['id', 'name'];
        foreach ($fields as $field) {
            $array = $this->$field;
            Log::debug(sprintf('Find mapped category based on field "%s" with value', $field), $array);
            // check if a pre-mapped object exists.
            $mapped = $this->getMappedObject($array);
            if (!is_null($mapped->id)) {
                Log::debug(sprintf('Found category #%d!', $mapped->id));

                return $mapped;
            }

        }
        Log::debug('Found no category on mapped data or no map present.');

        return new Category;
    }

    /**
     * @param array $array
     *
     * @return Category
     */
    private function getMappedObject(array $array): Category
    {
        Log::debug('In getMappedObject() for Category');
        if (count($array) === 0) {
            Log::debug('Array is empty, nothing will come of this.');

            return new Category;
        }

        if (array_key_exists('mapped', $array) && is_null($array['mapped'])) {
            Log::debug(sprintf('No map present for value "%s". Return NULL.', $array['value']));

            return new Category;
        }

        Log::debug('Finding a mapped category based on', $array);

        $search  = intval($array['mapped']);
        $category = $this->repository->find($search);

        if (is_null($category->id)) {
            Log::error(sprintf('There is no category with id #%d. Invalid mapping will be ignored!', $search));

            return new Category;
        }

        Log::debug(sprintf('Found category! #%d ("%s"). Return it', $category->id, $category->name));

        return $category;
    }

    /**
     * @return bool
     */
    private function store(): bool
    {
        // 1: find mapped object:
        $mapped = $this->findMappedObject();
        if (!is_null($mapped->id)) {
            $this->category = $mapped;

            return true;
        }
        // 2: find existing by given values:
        $found = $this->findExistingObject();
        if (!is_null($found->id)) {
            $this->category = $found;

            return true;
        }
        $name = $this->name['value'] ?? '';

        if (strlen($name) === 0) {
            return true;
        }

        Log::debug('Found no category so must create one ourselves.');

        $data = [
            'name' => $name,
        ];

        $this->category = $this->repository->store($data);
        Log::debug(sprintf('Successfully stored new category #%d: %s', $this->category->id, $this->category->name));

        return true;
    }


}
