<?php

namespace FireflyIII\Repositories\PiggyBank;

use Amount;
use Auth;
use Carbon\Carbon;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\Reminder;
use Illuminate\Support\Collection;
use Navigation;

/**
 * Class PiggyBankRepository
 *
 * @package FireflyIII\Repositories\PiggyBank
 */
class PiggyBankRepository implements PiggyBankRepositoryInterface
{

    /**
     * @SuppressWarnings("CyclomaticComplexity") // It's exactly 5. So I don't mind.
     *
     * Based on the piggy bank, the reminder-setting and
     * other variables this method tries to divide the piggy bank into equal parts. Each is
     * accommodated by a reminder (if everything goes to plan).
     *
     * @param PiggyBankRepetition $repetition
     *
     * @return Collection
     */
    public function calculateParts(PiggyBankRepetition $repetition)
    {
        /** @var PiggyBank $piggyBank */
        $piggyBank    = $repetition->piggyBank()->first();
        $bars         = new Collection;
        $currentStart = clone $repetition->startdate;

        if (is_null($piggyBank->reminder)) {
            $entry = ['repetition'    => $repetition, 'amountPerBar' => floatval($piggyBank->targetamount),
                      'currentAmount' => floatval($repetition->currentamount), 'cumulativeAmount' => floatval($piggyBank->targetamount),
                      'startDate'     => clone $repetition->startdate, 'targetDate' => clone $repetition->targetdate];
            $bars->push($this->createPiggyBankPart($entry));

            return $bars;
        }

        while ($currentStart < $repetition->targetdate) {
            $currentTarget = Navigation::endOfX($currentStart, $piggyBank->reminder, $repetition->targetdate);
            $entry         = ['repetition'       => $repetition, 'amountPerBar' => null, 'currentAmount' => floatval($repetition->currentamount),
                              'cumulativeAmount' => null, 'startDate' => $currentStart, 'targetDate' => $currentTarget];
            $bars->push($this->createPiggyBankPart($entry));
            $currentStart = clone $currentTarget;
            $currentStart->addDay();

        }
        $amountPerBar = floatval($piggyBank->targetamount) / $bars->count();
        $cumulative   = $amountPerBar;
        /** @var PiggyBankPart $bar */
        foreach ($bars as $index => $bar) {
            $bar->setAmountPerBar($amountPerBar);
            $bar->setCumulativeAmount($cumulative);
            if ($bars->count() - 1 == $index) {
                $bar->setCumulativeAmount($piggyBank->targetamount);
            }
            $cumulative += $amountPerBar;
        }

        return $bars;
    }

    /**
     * @param array $data
     *
     * @return PiggyBankPart
     */
    public function createPiggyBankPart(array $data)
    {
        $part = new PiggyBankPart;
        $part->setRepetition($data['repetition']);
        $part->setAmountPerBar($data['amountPerBar']);
        $part->setCurrentamount($data['currentAmount']);
        $part->setCumulativeAmount($data['cumulativeAmount']);
        $part->setStartdate($data['startDate']);
        $part->setTargetdate($data['targetDate']);

        return $part;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param Carbon    $currentStart
     * @param Carbon    $currentEnd
     *
     * @return Reminder
     */
    public function createReminder(PiggyBank $piggyBank, Carbon $currentStart, Carbon $currentEnd)
    {
        $reminder = Auth::user()->reminders()
                        ->where('remindersable_id', $piggyBank->id)
                        ->onDates($currentStart, $currentEnd)
                        ->first();
        if (is_null($reminder)) {
            // create one:
            $reminder = new Reminder;
            $reminder->user()->associate(Auth::user());
            $reminder->startdate = $currentStart;
            $reminder->enddate   = $currentEnd;
            $reminder->active    = true;
            $reminder->notnow    = false;
            $reminder->remindersable()->associate($piggyBank);
            $reminder->save();

            return $reminder;

        } else {
            return $reminder;
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
     *
     * @return array
     */
    public function getReminderRanges(PiggyBank $piggyBank)
    {
        $ranges = [];
        $today  = new Carbon;
        if ($piggyBank->remind_me === false) {
            return $ranges;
        }

        if (!is_null($piggyBank->targetdate)) {
            // count back until now.
            //                    echo 'Count back!<br>';
            $start = $piggyBank->targetdate;
            $end   = $piggyBank->startdate;

            while ($start >= $end) {
                $currentEnd   = clone $start;
                $start        = Navigation::subtractPeriod($start, $piggyBank->reminder, 1);
                $currentStart = clone $start;
                $ranges[]     = ['start' => clone $currentStart, 'end' => clone $currentEnd];
            }
        } else {
            $start = clone $piggyBank->startdate;
            while ($start < $today) {
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

        if (is_null($piggyBank->targetdate)) {
            return 'Add money to this piggy bank to reach your target of ' . Amount::format($piggyBank->targetamount);
        }

        $currentRep = $piggyBank->currentRelevantRep();


        $ranges = $this->getReminderRanges($piggyBank);
        // calculate number of reminders:
        $left        = $piggyBank->targetamount - $currentRep->currentamount;
        $perReminder = $left / count($ranges);

        return 'Add '.Amount::format($perReminder).' to fill this piggy bank on '.$piggyBank->targetdate->format('jS F Y');
    }

    /**
     * @param array $data
     *
     * @return PiggyBank
     */
    public function store(array $data)
    {
        $data['remind_me'] = isset($data['remind_me']) && $data['remind_me'] == '1' ? true : false;
        $piggyBank         = PiggyBank::create($data);

        return $piggyBank;
    }

    /**
     * @param PiggyBank $account
     * @param array     $data
     *
     * @return PiggyBank
     */
    public function update(PiggyBank $piggyBank, array $data)
    {
        /**
         * 'rep_length'   => $request->get('rep_length'),
         * 'rep_every'    => intval($request->get('rep_every')),
         * 'rep_times'    => intval($request->get('rep_times')),
         * 'remind_me'    => intval($request->get('remind_me')) == 1 ? true : false ,
         * 'reminder'     => $request->get('reminder'),
         */

        $piggyBank->name         = $data['name'];
        $piggyBank->account_id   = intval($data['account_id']);
        $piggyBank->targetamount = floatval($data['targetamount']);
        $piggyBank->targetdate   = $data['targetdate'];
        $piggyBank->reminder     = $data['reminder'];
        $piggyBank->startdate    = $data['startdate'];
        $piggyBank->rep_length   = isset($data['rep_length']) ? $data['rep_length'] : null;
        $piggyBank->rep_every    = isset($data['rep_every']) ? $data['rep_every'] : null;
        $piggyBank->rep_times    = isset($data['rep_times']) ? $data['rep_times'] : null;
        $piggyBank->remind_me    = isset($data['remind_me']) && $data['remind_me'] == '1' ? 1 : 0;

        $piggyBank->save();

        return $piggyBank;
    }
}