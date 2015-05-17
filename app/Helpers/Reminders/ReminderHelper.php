<?php

namespace FireflyIII\Helpers\Reminders;

use Amount;
use Auth;
use Carbon\Carbon;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Reminder;
use Navigation;

/**
 * Class ReminderHelper
 *
 * @package FireflyIII\Helpers\Reminders
 */
class ReminderHelper implements ReminderHelperInterface
{
    /**
     * @param PiggyBank $piggyBank
     * @param Carbon    $start
     * @param Carbon    $end
     *
     * @return Reminder
     */
    public function createReminder(PiggyBank $piggyBank, Carbon $start, Carbon $end)
    {
        $reminder = Auth::user()->reminders()->where('remindersable_id', $piggyBank->id)->onDates($start, $end)->first();
        if (is_null($reminder)) {

            if (!is_null($piggyBank->targetdate)) {
                // get ranges again, but now for the start date
                $ranges      = $this->getReminderRanges($piggyBank, $start);
                $currentRep  = $piggyBank->currentRelevantRep();
                $left        = $piggyBank->targetamount - $currentRep->currentamount;
                $perReminder = count($ranges) == 0 ? $left : $left / count($ranges);
            } else {
                $perReminder = null;
                $ranges      = [];
                $left        = 0;
            }
            $metaData = [
                'perReminder' => $perReminder,
                'rangesCount' => count($ranges),
                'ranges'      => $ranges,
                'leftToSave'  => $left,
            ];

            $reminder = new Reminder;
            $reminder->user()->associate(Auth::user());
            $reminder->startdate = $start;
            $reminder->enddate   = $end;
            $reminder->active    = true;
            $reminder->metadata  = $metaData;
            $reminder->notnow    = false;
            $reminder->remindersable()->associate($piggyBank);
            $reminder->save();

            return $reminder;

        } else {
            return $reminder;
        }
    }

    /**
     * Create all reminders for a piggy bank for a given date.
     *
     * @param PiggyBank $piggyBank
     *
     * @return mixed
     */
    public function createReminders(PiggyBank $piggyBank, Carbon $date)
    {
        $ranges = $this->getReminderRanges($piggyBank);

        foreach ($ranges as $range) {
            if ($date < $range['end'] && $date > $range['start']) {
                // create a reminder here!
                $this->createReminder($piggyBank, $range['start'], $range['end']);
                // stop looping, we're done.
                break;
            }

        }
    }

    /**
     * This routine will return an array consisting of two dates which indicate the start
     * and end date for each reminder that this piggy bank will have, if the piggy bank has
     * any reminders. For example:
     *
     * [12 mar - 15 mar]
     * [15 mar - 18 mar]
     *
     * etcetera.
     *
     * Array is filled with tiny arrays with Carbon objects in them.
     *
     * @param PiggyBank $piggyBank
     * @param Carbon    $date ;
     *
     * @return array
     */
    public function getReminderRanges(PiggyBank $piggyBank, Carbon $date = null)
    {
        $ranges = [];
        if (is_null($date)) {
            $date = new Carbon;
        }

        if ($piggyBank->remind_me === false) {
            return $ranges;
        }

        if (!is_null($piggyBank->targetdate)) {
            // count back until now.
            $start = $piggyBank->targetdate;
            $end   = $piggyBank->startdate;

            while ($start > $end) {
                $currentEnd   = clone $start;
                $start        = Navigation::subtractPeriod($start, $piggyBank->reminder, 1);
                $currentStart = clone $start;
                $ranges[]     = ['start' => clone $currentStart, 'end' => clone $currentEnd];
            }
        } else {
            $start = clone $piggyBank->startdate;
            while ($start < $date) {
                $currentStart = clone $start;
                $start        = Navigation::addPeriod($start, $piggyBank->reminder, 0);
                $currentEnd   = clone $start;
                $ranges[]     = ['start' => clone $currentStart, 'end' => clone $currentEnd];
            }
        }

        return $ranges;
    }

    /**
     * Takes a reminder, finds the piggy bank and tells you what to do now.
     * Aka how much money to put in.
     *
     *
     * @param Reminder $reminder
     *
     * @return string
     */
    public function getReminderText(Reminder $reminder)
    {
        /** @var PiggyBank $piggyBank */
        $piggyBank = $reminder->remindersable;

        if (is_null($piggyBank)) {
            return 'Piggy bank no longer exists.';
        }

        if (is_null($piggyBank->targetdate)) {
            return 'Add money to this piggy bank to reach your target of ' . Amount::format($piggyBank->targetamount);
        }

        return 'Add ' . Amount::format($reminder->metadata->perReminder) . ' to fill this piggy bank on ' . $piggyBank->targetdate->format('jS F Y');

    }
}
