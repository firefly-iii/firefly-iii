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

        /** @var \FireflyIII\Shared\Toolkit\Date $dateKit */
        $dateKit = \App::make('FireflyIII\Shared\Toolkit\Date');

        switch (get_class($reminder->remindersable)) {

            case 'Piggybank':
                $start     = new Carbon;
                $end       = !is_null($reminder->remindersable->targetdate) ? clone $reminder->remindersable->targetdate : new Carbon;
                $reminders = 0;
                while ($start <= $end) {
                    $reminders++;
                    $start = $dateKit->addPeriod($start, $reminder->remindersable->reminder, $reminder->remindersable->reminder_skip);
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


        return 50;
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
        //        $reminders = \Auth::user()->reminders()->where('active', true)->get();
        //        $return    = [];
        //        /** @var \Reminder $reminder */
        //        foreach ($reminders as $reminder) {
        //            $set = ['id' => $reminder->id];
        //            switch ($reminder->data->type) {
        //                case 'Test':
        //                case 'Piggybank':
        //                    $set['title'] = $reminder->title;
        //                    $set['icon']  = $reminder->data->icon;
        //                    $set['text']  = mf(floatval($reminder->data->amount));
        //                    break;
        //
        //            }
        //            $return[] = $set;
        //        }
        //
        //        return $return;
    }

    public function updateReminders()
    {

        /*
         * Reminder capable objects are (so far) only piggy banks.
         */
        /** @var \FireflyIII\Database\Piggybank $repository */
        $repository = \App::make('FireflyIII\Database\Piggybank');

        /** @var \FireflyIII\Shared\Toolkit\Date $dateKit */
        $dateKit = \App::make('FireflyIII\Shared\Toolkit\Date');


        /** @var Collection $piggybanks */
        $piggybanks = $repository->get();
        $set        = $piggybanks->filter(
            function (\Piggybank $piggybank) {
                if (!is_null($piggybank->reminder)) {
                    return $piggybank;
                }
            }
        );

        /** @var \Piggybank $piggybank */
        foreach ($set as $piggybank) {
            /*
             * Try to find a reminder that is valid in the current [period]
             * aka between [start of period] and [end of period] as denoted
             * by the piggy's repeat_freq.
             */
            /** @var \PiggybankRepetition $repetition */
            $repetition = $piggybank->currentRelevantRep();
            $start      = $dateKit->startOfPeriod(Carbon::now(), $piggybank->reminder);
            if ($repetition->targetdate && $repetition->targetdate <= Carbon::now()) {
                // break when no longer relevant:
                continue;
            }
            $end = $dateKit->endOfPeriod(clone $start, $piggybank->reminder);
            // should have a reminder for this period:
            /** @var \Collection $reminders */
            $reminders = $piggybank->reminders()->dateIs($start, $end)->get();
            if ($reminders->count() == 0) {
                // create new!
                $reminder            = new \Reminder;
                $reminder->startdate = $start;
                $reminder->enddate   = $end;
                $reminder->user()->associate($repository->getUser());
                $reminder->remindersable()->associate($piggybank);
                $reminder->save();
            }
        }
    }
} 