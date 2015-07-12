<?php
namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use FireflyIII\Models\Budget;

/**
 * Class AccountId
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class BudgetId extends BasicConverter implements ConverterInterface
{

    /**
     * @return Budget
     */
    public function convert()
    {
        // is mapped? Then it's easy!
        if (isset($this->mapped[$this->index][$this->value])) {
            $budget = Auth::user()->budgets()->find($this->mapped[$this->index][$this->value]);
        } else {
            $budget = Auth::user()->budgets()->find($this->value);
        }

        return $budget;
    }
}
