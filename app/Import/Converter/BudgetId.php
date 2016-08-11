<?php
/**
 * BudgetId.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Converter;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Log;

/**
 * Class BudgetId
 *
 * @package FireflyIII\Import\Converter
 */
class BudgetId extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return Budget
     */
    public function convert($value)
    {
        $value = intval(trim($value));
        Log::debug('Going to convert using BudgetId', ['value' => $value]);

        if ($value === 0) {
            $this->setCertainty(0);
            return new Budget;
        }

        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class, [$this->user]);

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
        $budget = $repository->find($value);
        if (!is_null($budget->id)) {
            Log::debug('Found budget by ID ', ['id' => $budget->id]);
            $this->setCertainty(100);
            return $budget;
        }

        // should not really happen. If the ID does not match FF, what is FF supposed to do?
        $this->setCertainty(0);

        Log::info(sprintf('Could not find budget with ID %d. Will return NULL', $value));

        return new Budget;

    }
}