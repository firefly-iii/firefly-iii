<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use FireflyIII\Models\Budget;

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
    public function convert()
    {
        // is mapped? Then it's easy!
        if (isset($this->mapped[$this->index][$this->value])) {
            $budget = Auth::user()->budgets()->find($this->mapped[$this->index][$this->value]);
        } else {
            $budget = Budget::firstOrCreateEncrypted(
                [
                    'name'    => $this->value,
                    'user_id' => Auth::user()->id,
                    'active'  => true,
                ]
            );
        }

        return $budget;
    }
}
