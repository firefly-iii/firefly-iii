<?php
namespace FireflyIII\Event;


use Illuminate\Database\QueryException;
use Illuminate\Events\Dispatcher;

class Budget
{

    /**
     * @param \Limit $limit
     */
    public function storeOrUpdateLimit(\Limit $limit)
    {
        /** @var \FireflyIII\Shared\Toolkit\Date $dateKit */
        $dateKit = \App::make('FireflyIII\Shared\Toolkit\Date');


        $end = $dateKit->addPeriod(clone $limit->startdate, $limit->repeat_freq, 0);
        $end->subDay();

        $set = $limit->limitrepetitions()->where('startdate', $limit->startdate->format('Y-m-d'))->where('enddate', $end->format('Y-m-d'))->get();
        /*
         * Create new LimitRepetition:
         */
        if ($set->count() == 0) {

            $repetition            = new \LimitRepetition();
            $repetition->startdate = $limit->startdate;
            $repetition->enddate   = $end;
            $repetition->amount    = $limit->amount;
            $repetition->limit()->associate($limit);

            try {
                $repetition->save();
            } catch (QueryException $e) {
                \Log::error('Trying to save new Limitrepetition failed!');
                \Log::error($e->getMessage());
            }
        } else {
            if ($set->count() == 1) {
                /*
                 * Update existing one.
                 */
                $repetition         = $set->first();
                $repetition->amount = $limit->amount;
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