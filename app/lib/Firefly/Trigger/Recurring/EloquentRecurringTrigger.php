<?php

namespace Firefly\Trigger\Recurring;

use Carbon\Carbon;
use Illuminate\Events\Dispatcher;

/**
 * Class EloquentRecurringTrigger
 *
 * @package Firefly\Trigger\Recurring
 */
class EloquentRecurringTrigger
{

    /**
     * @param \RecurringTransaction $recurring
     */
    public function destroy(\RecurringTransaction $recurring)
    {
        $reminders = $recurring->recurringtransactionreminders()->get();
        /** @var \RecurringTransactionReminder $reminder */
        foreach ($reminders as $reminder) {
            $reminder->delete();
        }

        return true;

    }

    /**
     * @param \RecurringTransaction $recurring
     */
    public function store(\RecurringTransaction $recurring)
    {
        $this->createReminders();

    }

    public function createReminders()
    {
        $entries = \Auth::user()->recurringtransactions()->where('active', 1)->get();

        // for each entry, check for existing reminders during their period:
        /** @var \RecurringTransaction $entry */
        foreach ($entries as $entry) {

            $start = clone $entry->date;
            $end = clone $entry->date;
            switch ($entry->repeat_freq) {
                case 'weekly':
                    $start->startOfWeek();
                    $end->endOfWeek();
                    break;
                case 'monthly':
                    $start->startOfMonth();
                    $end->endOfMonth();
                    break;
                case 'quarterly':
                    $start->firstOfQuarter();
                    $end->lastOfQuarter();
                    break;
                case 'half-year':
                    // start of half-year:
                    if (intval($start->format('m')) >= 7) {
                        $start->startOfYear();
                        $start->addMonths(6);
                    } else {
                        $start->startOfYear();
                    }
                    $end = clone $start;
                    $end->addMonths(6);
                    break;
                case 'yearly':
                    $start->startOfYear();
                    $end->endOfYear();
                    break;
            }
            // check if exists.
            $count = $entry->reminders()->where('startdate', $start->format('Y-m-d'))->where(
                'enddate', $end->format('Y-m-d')
            )->count();
            if ($count == 0) {
                // create reminder:
                $reminder = new \RecurringTransactionReminder;
                $reminder->recurringtransaction()->associate($entry);
                $reminder->startdate = $start;
                $reminder->enddate = $end;
                $reminder->active = 1;
                $reminder->user()->associate(\Auth::user());
                $reminder->save();
            }


        }

    }

    /**
     * Trigger!
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen('recurring.destroy', 'Firefly\Trigger\Recurring\EloquentRecurringTrigger@destroy');
        $events->listen('recurring.store', 'Firefly\Trigger\Recurring\EloquentRecurringTrigger@store');
        $events->listen('recurring.update', 'Firefly\Trigger\Recurring\EloquentRecurringTrigger@update');
        $events->listen('recurring.check', 'Firefly\Trigger\Recurring\EloquentRecurringTrigger@createReminders');
    }

    /**
     * @param \RecurringTransaction $recurring
     */
    public function update(\RecurringTransaction $recurring)
    {
        // remove old active reminders
        $reminders = $recurring->reminders()->validOnOrAfter(new Carbon)->get();
        foreach ($reminders as $r) {
            $r->delete();
        }
        $this->createReminders();
        // create new reminder for the current period.

        // and now create new one(s)!
    }
} 