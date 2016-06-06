<?php
/**
 * BudgetId.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;

/**
 * Class BudgetId
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class BudgetId extends BasicConverter implements ConverterInterface
{

    /**
     * @return Budget
     */
    public function convert(): Budget
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $value      = isset($this->mapped[$this->index][$this->value]) ? $this->mapped[$this->index][$this->value] : $this->value;
        $budget     = $repository->find($value);

        return $budget;
    }
}
