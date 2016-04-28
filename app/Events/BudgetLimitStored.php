<?php
/**
 * BudgetLimitStored.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Events;

use Carbon\Carbon;
use FireflyIII\Models\BudgetLimit;
use Illuminate\Queue\SerializesModels;
use Log;

/**
 * Class BudgetLimitStored
 *
 * @package FireflyIII\Events
 */
class BudgetLimitStored extends Event
{

    use SerializesModels;

    /** @var  BudgetLimit */
    public $budgetLimit;

    /** @var  Carbon */
    public $end; // the only variable we can't get from the budget limit (if necessary).

    /**
     * BudgetLimitEvents constructor.
     *
     * @param BudgetLimit $budgetLimit
     * @param Carbon      $end
     */
    public function __construct(BudgetLimit $budgetLimit, Carbon $end)
    {
        Log::debug('Created new BudgetLimitStored.');
        //
        $this->budgetLimit = $budgetLimit;
        $this->end         = $end;

    }

}
