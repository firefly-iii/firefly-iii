<?php

namespace FireflyIII\Shared\Toolkit;

use Carbon\Carbon;
use FireflyIII\Exception\FireflyException;
use Illuminate\Support\Collection;

/**
 * Class Reminders
 *
 * @package FireflyIII\Shared\Toolkit
 */
class Reminders
{

    /**
     * @param \Reminder $reminder
     *
     * @return int
     * @throws FireflyException
     */
    public function amountForReminder(\Reminder $reminder)
    {

        switch (get_class($reminder->remindersable)) {

            case 'PiggyBank':
                $start     = new Carbon;
                $end       = !is_null($reminder->remindersable->targetdate) ? clone $reminder->remindersable->targetdate : new Carbon;
                $reminders = 0;
                while ($start <= $end) {
                    $reminders++;
                    $start = \DateKit::addPeriod($start, $reminder->remindersable->reminder, $reminder->remindersable->reminder_skip);
                }
                /*
                 * Now find amount yet to save.
                 */
                $repetition = $reminder->remindersable->currentRelevantRep();
                $leftToSave = floatval($reminder->remindersable->targetamount) - floatval($repetition->currentamount);
                $reminders  = $reminders == 0 ? 1 : $reminders;

                return $leftToSave / $reminders;
                break;
            default:
                throw new FireflyException('Cannot handle class ' . get_class($reminder->remindersable) . ' in amountForReminder.');
                break;
        }
    }

    /**
     *
     */
    public function getReminders()
    {
        $reminders = \Auth::user()->reminders()
                          ->where('active', 1)
                          ->where('startdate', '<=', Carbon::now()->format('Y-m-d'))
                          ->where('enddate', '>=', Carbon::now()->format('Y-m-d'))
                          ->get();

        return $reminders;
    }

    public function updateReminders()
    {
        /** @var Collection $set */
        $set = \PiggyBank::leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')
                         ->where('accounts.user_id', \Auth::user()->id)
                         ->whereNotNull('reminder')->get(['piggy_banks.*']);


        $today = Carbon::now();

        /** @var \PiggyBank $piggyBank */
        foreach ($set as $piggyBank) {
            /** @var \PiggyBankRepetition $repetition */
            $repetition = $piggyBank->currentRelevantRep();
            $start      = \DateKit::startOfPeriod($today, $piggyBank->reminder);
            if ($repetition->targetdate && $repetition->targetdate <= $today) {
                // break when no longer relevant:
                continue;
            }
            $end = \DateKit::endOfPeriod(clone $start, $piggyBank->reminder);
            // should have a reminder for this period:
            /** @var Collection $reminders */
            $reminders = $piggyBank->reminders()->dateIs($start, $end)->get();
            if ($reminders->count() == 0) {
                // create new!
                $reminder            = new \Reminder;
                $reminder->startdate = $start;
                $reminder->enddate   = $end;
                $reminder->active    = 1;
                $reminder->user()->associate(\Auth::getUser());
                $reminder->remindersable_id   = $piggyBank->id;
                $reminder->remindersable_type = 'PiggyBank';
                $reminder->save();
            }
        }
    }
} 