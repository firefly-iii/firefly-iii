<?php
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
        $repository = app('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $value      = isset($this->mapped[$this->index][$this->value]) ? $this->mapped[$this->index][$this->value] : $this->value;
        $budget     = $repository->find($value);

        return $budget;
    }
}
