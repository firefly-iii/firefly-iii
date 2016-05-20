<?php
/**
 * BudgetName.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;

/**
 * Class BudgetName
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class BudgetName extends BasicConverter implements ConverterInterface
{

    /**
     * @return Budget
     */
    public function convert(): Budget
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);

        // is mapped? Then it's easy!
        if (isset($this->mapped[$this->index][$this->value])) {
            $budget = $repository->find($this->mapped[$this->index][$this->value]);

            return $budget;
        }
        $budget = $repository->store(['name' => $this->value, 'user' => Auth::user()->id]);


        return $budget;
    }
}
