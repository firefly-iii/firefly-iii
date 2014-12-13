<?php
namespace FireflyIII\Event;


use Illuminate\Database\QueryException;
use Illuminate\Events\Dispatcher;

/**
 * Class Budget
 *
 * @package FireflyIII\Event
 */
class Budget
{

    /**
     * @param \BudgetLimit $budgetLimit
     */
    public function storeOrUpdateLimit(\BudgetLimit $budgetLimit)
    {


        $end = \DateKit::addPeriod(clone $budgetLimit->startdate, $budgetLimit->repeat_freq, 0);
        $end->subDay();

        $set = $budgetLimit->limitrepetitions()->where('startdate', $budgetLimit->startdate->format('Y-m-d'))->where('enddate', $end->format('Y-m-d'))->get();
        /*
         * Create new LimitRepetition:
         */
        if ($set->count() == 0) {

            $repetition            = new \LimitRepetition();
            $repetition->startdate = $budgetLimit->startdate;
            $repetition->enddate   = $end;
            $repetition->amount    = $budgetLimit->amount;
            $repetition->budgetLimit()->associate($budgetLimit);

            try {
                $repetition->save();
            } catch (QueryException $e) {
                \Log::error('Trying to save new LimitRepetition failed!');
                \Log::error($e->getMessage());
            }
        } else {
            if ($set->count() == 1) {
                /*
                 * Update existing one.
                 */
                $repetition         = $set->first();
                $repetition->amount = $budgetLimit->amount;
                $repetition->save();

            }
        }

    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen('limits.store', 'FireflyIII\Event\Budget@storeOrUpdateLimit');
        $events->listen('limits.update', 'FireflyIII\Event\Budget@storeOrUpdateLimit');

    }
} 