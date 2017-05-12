<?php
/**
 * BudgetName.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Converter;

use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Log;

/**
 * Class BudgetName
 *
 * @package FireflyIII\Import\Converter
 */
class BudgetName extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return Budget
     */
    public function convert($value)
    {
        $value = trim($value);
        Log::debug('Going to convert using BudgetName', ['value' => $value]);

        if (strlen($value) === 0) {
            $this->setCertainty(0);

            return new Budget;
        }

        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $repository->setUser($this->user);

        if (isset($this->mapping[$value])) {
            Log::debug('Found budget in mapping. Should exist.', ['value' => $value, 'map' => $this->mapping[$value]]);
            $budget = $repository->find(intval($this->mapping[$value]));
            if (!is_null($budget->id)) {
                Log::debug('Found budget by ID', ['id' => $budget->id]);
                $this->setCertainty(100);

                return $budget;
            }
        }

        // not mapped? Still try to find it first:
        $budget = $repository->findByName($value);
        if (!is_null($budget->id)) {
            Log::debug('Found budget by name ', ['id' => $budget->id]);
            $this->setCertainty(100);

            return $budget;
        }

        // create new budget. Use a lot of made up values.
        $budget = $repository->store(
            [
                'name' => $value,
                'user' => $this->user->id,
            ]
        );
        $this->setCertainty(100);

        return $budget;

    }
}
