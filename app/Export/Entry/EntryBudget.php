<?php
/**
 * EntryBudget.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export\Entry;

use FireflyIII\Models\Budget;

/**
 * Class EntryBudget
 *
 * @package FireflyIII\Export\Entry
 */
class EntryBudget
{
    /** @var  int */
    public $budgetId = '';
    /** @var  string */
    public $name = '';

    /**
     * EntryBudget constructor.
     *
     * @param Budget $budget
     */
    public function __construct(Budget $budget = null)
    {
        if (!is_null($budget)) {
            $this->budgetId = $budget->id;
            $this->name     = $budget->name;
        }
    }

}