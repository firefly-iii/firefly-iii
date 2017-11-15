<?php
/**
 * ImportBudget.php
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

namespace FireflyIII\Import\Object;

use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class ImportBudget
 *
 * @package FireflyIII\Import\Object
 */
class ImportBudget
{

    /** @var Budget */
    private $budget;
    /** @var array */
    private $id = [];
    /** @var array */
    private $name = [];
    /** @var BudgetRepositoryInterface */
    private $repository;
    /** @var  User */
    private $user;

    /**
     * ImportBudget constructor.
     */
    public function __construct()
    {
        $this->budget     = new Budget;
        $this->repository = app(BudgetRepositoryInterface::class);
        Log::debug('Created ImportBudget.');
    }

    /**
     * @return Budget
     */
    public function getBudget(): Budget
    {
        if (is_null($this->budget->id)) {
            $this->store();
        }

        return $this->budget;
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
     * @return Budget
     */
    private function findExistingObject(): Budget
    {
        Log::debug('In findExistingObject() for Budget');
        // 1: find by ID, or name

        if (count($this->id) === 3) {
            Log::debug(sprintf('Finding budget with ID #%d', $this->id['value']));
            /** @var Budget $budget */
            $budget = $this->repository->find(intval($this->id['value']));
            if (!is_null($budget->id)) {
                Log::debug(sprintf('Found unmapped budget by ID (#%d): %s', $budget->id, $budget->name));

                return $budget;
            }
            Log::debug('Found nothing.');
        }
        // 2: find by name
        if (count($this->name) === 3) {
            /** @var Collection $budgets */
            $budgets = $this->repository->getBudgets();
            $name    = $this->name['value'];
            Log::debug(sprintf('Finding budget with name %s', $name));
            $filtered = $budgets->filter(
                function (Budget $budget) use ($name) {
                    if ($budget->name === $name) {
                        Log::debug(sprintf('Found unmapped budget by name (#%d): %s', $budget->id, $budget->name));

                        return $budget;
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
        Log::debug('Found NO existing budgets.');

        return new Budget;
    }

    /**
     * @return Budget
     */
    private function findMappedObject(): Budget
    {
        Log::debug('In findMappedObject() for Budget');
        $fields = ['id', 'name'];
        foreach ($fields as $field) {
            $array = $this->$field;
            Log::debug(sprintf('Find mapped budget based on field "%s" with value', $field), $array);
            // check if a pre-mapped object exists.
            $mapped = $this->getMappedObject($array);
            if (!is_null($mapped->id)) {
                Log::debug(sprintf('Found budget #%d!', $mapped->id));

                return $mapped;
            }
        }
        Log::debug('Found no budget on mapped data or no map present.');

        return new Budget;
    }

    /**
     * @param array $array
     *
     * @return Budget
     */
    private function getMappedObject(array $array): Budget
    {
        Log::debug('In getMappedObject() for Budget');
        if (count($array) === 0) {
            Log::debug('Array is empty, nothing will come of this.');

            return new Budget;
        }

        if (array_key_exists('mapped', $array) && is_null($array['mapped'])) {
            Log::debug(sprintf('No map present for value "%s". Return NULL.', $array['value']));

            return new Budget;
        }

        Log::debug('Finding a mapped budget based on', $array);

        $search = intval($array['mapped']);
        $budget = $this->repository->find($search);

        if (is_null($budget->id)) {
            Log::error(sprintf('There is no budget with id #%d. Invalid mapping will be ignored!', $search));

            return new Budget;
        }

        Log::debug(sprintf('Found budget! #%d ("%s"). Return it', $budget->id, $budget->name));

        return $budget;
    }

    /**
     * @return bool
     */
    private function store(): bool
    {
        // 1: find mapped object:
        $mapped = $this->findMappedObject();
        if (!is_null($mapped->id)) {
            $this->budget = $mapped;

            return true;
        }
        // 2: find existing by given values:
        $found = $this->findExistingObject();
        if (!is_null($found->id)) {
            $this->budget = $found;

            return true;
        }
        $name = $this->name['value'] ?? '';

        if (strlen($name) === 0) {
            return true;
        }

        Log::debug('Found no budget so must create one ourselves.');

        $data = [
            'name' => $name,
        ];

        $this->budget = $this->repository->store($data);
        Log::debug(sprintf('Successfully stored new budget #%d: %s', $this->budget->id, $this->budget->name));

        return true;
    }
}
