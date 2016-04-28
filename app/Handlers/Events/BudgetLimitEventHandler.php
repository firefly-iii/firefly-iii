<?php
/**
 * BudgetLimitEventHandler.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\BudgetLimitStored;
use FireflyIII\Events\BudgetLimitUpdated;
use FireflyIII\Models\LimitRepetition;
use Illuminate\Database\QueryException;
use Log;

/**
 * Class BudgetLimitEventHandler
 *
 * @package FireflyIII\Handlers\Events
 */
class BudgetLimitEventHandler
{
    /**
     * Create the event listener.
     *
     */
    public function __construct()
    {

    }

    /**
     * In a perfect world, the store() routine should be different from the update()
     * routine. It would not have to check count() == 0 because there could be NO
     * limit repetitions at this point. However, the database can be wrong so we check.
     *
     * @param BudgetLimitStored $event
     */
    public function store(BudgetLimitStored $event)
    {
        $budgetLimit = $event->budgetLimit;
        $end         = $event->end;
        $set         = $budgetLimit->limitrepetitions()
                                   ->where('startdate', $budgetLimit->startdate->format('Y-m-d 00:00:00'))
                                   ->where('enddate', $end->format('Y-m-d 00:00:00'))
                                   ->get();
        if ($set->count() == 0) {
            $repetition            = new LimitRepetition;
            $repetition->startdate = $budgetLimit->startdate;
            $repetition->enddate   = $end;
            $repetition->amount    = $budgetLimit->amount;
            $repetition->budgetLimit()->associate($budgetLimit);

            try {
                $repetition->save();
            } catch (QueryException $e) {
                Log::error('Trying to save new LimitRepetition failed: ' . $e->getMessage());
            }
        }

        if ($set->count() == 1) {
            $repetition         = $set->first();
            $repetition->amount = $budgetLimit->amount;
            $repetition->save();

        }

    }

    /**
     * @param BudgetLimitUpdated $event
     */
    public function update(BudgetLimitUpdated $event)
    {
        $budgetLimit = $event->budgetLimit;
        $end         = $event->end;
        $set         = $budgetLimit->limitrepetitions()
                                   ->where('startdate', $budgetLimit->startdate->format('Y-m-d 00:00:00'))
                                   ->where('enddate', $end->format('Y-m-d 00:00:00'))
                                   ->get();
        if ($set->count() == 0) {
            $repetition            = new LimitRepetition;
            $repetition->startdate = $budgetLimit->startdate;
            $repetition->enddate   = $end;
            $repetition->amount    = $budgetLimit->amount;
            $repetition->budgetLimit()->associate($budgetLimit);

            try {
                $repetition->save();
            } catch (QueryException $e) {
                Log::error('Trying to save new LimitRepetition failed: ' . $e->getMessage());
            }
        }

        if ($set->count() == 1) {
            $repetition         = $set->first();
            $repetition->amount = $budgetLimit->amount;
            $repetition->save();

        }
    }

}