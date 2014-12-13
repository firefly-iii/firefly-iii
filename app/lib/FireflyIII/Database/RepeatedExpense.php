<?php

namespace FireflyIII\Database;


use Carbon\Carbon;
use FireflyIII\Collection\PiggybankPart;
use FireflyIII\Database\Ifaces\CommonDatabaseCalls;
use FireflyIII\Database\Ifaces\CUD;
use FireflyIII\Database\Ifaces\PiggybankInterface;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;


/**
 * Class RepeatedExpense
 *
 * @package FireflyIII\Database
 */
class RepeatedExpense implements CUD, CommonDatabaseCalls, PiggybankInterface
{
    use SwitchUser;

    /**
     *
     */
    public function __construct()
    {
        $this->setUser(\Auth::user());
    }

    /**
     * Based on the piggy bank, the reminder-setting and
     * other variables this method tries to divide the piggy bank into equal parts. Each is
     * accommodated by a reminder (if everything goes to plan).
     *
     * @param \PiggybankRepetition $repetition
     *
     * @return \PiggybankRepetition
     */
    public function calculateParts(\PiggybankRepetition $repetition)
    {
        \Log::debug('NOW in calculateParts()');
        \Log::debug('Repetition id is ' . $repetition->id);
        /** @var \Piggybank $piggyBank */
        $piggyBank = $repetition->piggybank()->first();
        $bars      = new Collection;
        \Log::debug('connected piggy bank is: ' . $piggyBank->name . ' (#' . $piggyBank->id . ')');

        /*
         * If no reminders are set, the repetition is split in exactly one part:
         */
        if (is_null($piggyBank->reminder)) {
            $part = new PiggybankPart;
            $part->setRepetition($repetition);
            $part->setAmountPerBar(floatval($piggyBank->targetamount));
            $part->setCurrentamount($repetition->currentamount);
            $part->setCumulativeAmount($piggyBank->targetamount);
            $part->setStartdate(clone $repetition->startdate);
            $part->setTargetdate(clone $repetition->targetdate);
            $bars->push($part);
            $repetition->bars = $bars;

            return $repetition;
        }
        $currentStart = clone $repetition->startdate;
        /*
         * Loop between start and target instead of counting manually.
         */
        $index = 0;
        //echo 'Looping!<br>';
        //echo $repetition->startdate . ' until ' . $repetition->targetdate . '<br>';
        while ($currentStart < $repetition->targetdate) {
            $currentTarget = \DateKit::endOfX($currentStart, $piggyBank->reminder);
            if ($currentTarget > $repetition->targetdate) {
                $currentTarget = clone $repetition->targetdate;
            }

            // create a part:
            $part = new PiggybankPart;
            $part->setRepetition($repetition);
            $part->setCurrentamount($repetition->currentamount);
            $part->setStartdate($currentStart);
            $part->setTargetdate($currentTarget);
            $bars->push($part);
            //echo 'Loop #' . $index . ', from ' . $currentStart . ' until ' . $currentTarget . '<br />';


            /*
             * Jump to the next period by adding a day.
             */
            $currentStart = clone $currentTarget;
            $currentStart->addDay();//\DateKit::addPeriod($currentTarget, $piggyBank->reminder, 0);
            $index++;

        }
        /*
         * Loop parts again to add some
         */
        $parts        = $bars->count();
        $amountPerBar = floatval($piggyBank->targetamount) / $parts;
        $cumulative   = $amountPerBar;
        /** @var PiggybankPart $bar */
        foreach ($bars as $index => $bar) {
            $bar->setAmountPerBar($amountPerBar);
            $bar->setCumulativeAmount($cumulative);
            if ($parts - 1 == $index) {
                $bar->setCumulativeAmount($piggyBank->targetamount);
            }

            $reminder = $piggyBank->reminders()
                                  ->where('startdate', $bar->getStartdate()->format('Y-m-d'))
                                  ->where('enddate', $bar->getTargetdate()->format('Y-m-d'))
                                  ->first();
            if ($reminder) {
                $bar->setReminder($reminder);
            }

            $cumulative += $amountPerBar;
        }

        $repetition->bars = $bars;

        return $repetition;
        //
        //        //        if($parts > 12) {
        //        //            $parts = 12;
        //        //            $currentStart = \DateKit::startOfPeriod(Carbon::now(), $piggyBank->reminder);
        //        //            $currentEnd = \DateKit::endOfPeriod($currentEnd, $piggyBank->reminder);
        //        //        }
        //
        //        for ($i = 0; $i < $parts; $i++) {
        //            /*
        //             * If it's not the first repetition, jump the start date a [period]
        //             * and jump the target date a [period]
        //             */
        //            if ($i > 0) {
        //                $currentStart = clone $currentTarget;
        //                $currentStart->addDay();
        //                $currentTarget = \DateKit::addPeriod($currentStart, $piggyBank->reminder, 0);
        //            }
        //            /*
        //             * If it's the first one, and has reminders, jump to the end of the [period]
        //             */
        //            if ($i == 0 && !is_null($piggyBank->reminder)) {
        //                $currentTarget = \DateKit::endOfX($currentStart, $piggyBank->reminder);
        //            }
        //            if ($currentStart > $repetition->targetdate) {
        //                break;
        //            }
        //
        //
        //            /*
        //             * Jump one month ahead after the first instance:
        //             */
        //            //            if ($i > 0) {
        //            //                $currentStart = \DateKit::addPeriod($currentStart, $piggyBank->reminder, 0);
        //            //                /*
        //            //                 * Jump to the start of the period too:
        //            //                 */
        //            //                $currentStart = \DateKit::startOfPeriod($currentStart, $piggyBank->reminder);
        //            //
        //            //            }
        //
        //
        //            /*
        //             * Move the current start to the actual start of
        //             * the [period] once the first iteration has passed.
        //             */
        //            //            if ($i != 0) {
        //            //                $currentStart = \DateKit::startOfPeriod($currentStart, $piggyBank->reminder);
        //            //            }
        //            //            if($i == 0 && !is_null($piggyBank->reminder)) {
        //            //                $currentEnd = \DateKit::startOfPeriod($currentStart, $piggyBank->reminder);
        //            //                $currentEnd = \DateKit::endOfPeriod($currentEnd, $piggyBank->reminder);
        //            //            }
        //
        //            $part = new PiggybankPart;
        //            $part->setRepetition($repetition);
        //            $part->setAmount($currentAmount);
        //            $part->setAmountPerBar($amountPerBar);
        //            $part->setCurrentamount($repetition->currentamount);
        //            $part->setStartdate($currentStart);
        //            $part->setTargetdate($currentTarget);
        //            if (!is_null($piggyBank->reminder)) {
        //                // might be a reminder for this range?
        //                $reminder = $piggyBank->reminders()
        //                                      ->where('startdate', $currentStart->format('Y-m-d'))
        //                                      ->where('enddate', $currentTarget->format('Y-m-d'))
        //                                      ->first();
        //                if ($reminder) {
        //                    $part->setReminder($reminder);
        //                }
        //
        //            }
        //
        //            //            if (!is_null($piggyBank->reminder)) {
        //            //                $currentStart = \DateKit::addPeriod($currentStart, $piggyBank->reminder, 0);
        //            //                $currentEnd   = \DateKit::endOfPeriod($currentStart, $piggyBank->reminder);
        //            //            }
        //
        //
        //            $bars->push($part);
        //            $currentAmount += $amountPerBar;
        //        }
        //        $repetition->bars = $bars;
        //
        //        return $repetition;
        //        exit;
        //
        //
        //        $repetition->hello = 'World!';
        //
        //        return $repetition;
        //
        //        $return      = new Collection;
        //        $repetitions = $piggyBank->piggybankrepetitions()->get();
        //        /** @var \PiggybankRepetition $repetition */
        //        foreach ($repetitions as $repetition) {
        //
        //
        //            if (is_null($piggyBank->reminder)) {
        //                // simple, one part "repetition".
        //                $part = new PiggybankPart;
        //                $part->setRepetition($repetition);
        //            } else {
        //                $part = new PiggybankPart;
        //            }
        //
        //
        //            // end!
        //            $return->push($part);
        //        }
        //
        //        exit;
        //
        //        return $return;
        //        $piggyBank->currentRelevantRep(); // get the current relevant repetition.
        //        if (!is_null($piggyBank->reminder)) {
        //            switch ($piggyBank->reminder) {
        //                default:
        //                    throw new FireflyException('Cannot handle "' . $piggyBank->reminder . '" reminders for repeated expenses');
        //                    break;
        //                case 'month':
        //                    $start = clone $piggyBank->currentRep->startdate;
        //                    $start->startOfMonth();
        //                    $end = clone $piggyBank->currentRep->targetdate;
        //                    $end->endOfMonth();
        //                    $piggyBank->parts = $start->diffInMonths($end);
        //                    unset($start, $end);
        //                    break;
        //            }
        //
        //        } else {
        //            $piggyBank->parts = 1;
        //        }
        //
        //        // number of bars:
        //        $piggyBank->barCount = floor(12 / $piggyBank->parts) == 0 ? 1 : floor(12 / $piggyBank->parts);
        //        $amountPerBar        = floatval($piggyBank->targetamount) / $piggyBank->parts;
        //        $currentAmount       = floatval($amountPerBar);
        //        $bars                = [];
        //        $currentStart        = clone $piggyBank->currentRep->startdate;
        //        for ($i = 0; $i < $piggyBank->parts; $i++) {
        //            // niet elke keer een andere dinges pakken? om target te redden?
        //            if (!is_null($piggyBank->reminder)) {
        //                $currentStart = \DateKit::addPeriod($currentStart, $piggyBank->reminder, 0);
        //            }
        //            $bars[] = [
        //                'amount' => $currentAmount,
        //                'date'   => $currentStart
        //            ];
        //
        //
        //            $currentAmount += $amountPerBar;
        //        }
        //        $piggyBank->bars = $bars;
    }

    /**
     * @param \Eloquent $model
     *
     * @return bool
     * @throws NotImplementedException
     */
    public function destroy(\Eloquent $model)
    {
        // TODO: Implement destroy() method.
        throw new NotImplementedException;
    }

    /**
     * @param array $data
     *
     * @return \Eloquent
     */
    public function store(array $data)
    {

        $data['rep_every']     = isset($data['rep_every']) ? $data['rep_every'] : 0;
        $data['reminder_skip'] = isset($data['reminder_skip']) ? $data['reminder_skip'] : 0;
        $data['order']         = isset($data['order']) ? $data['order'] : 0;
        $data['remind_me']     = isset($data['remind_me']) ? intval($data['remind_me']) : 0;
        $data['startdate']     = isset($data['startdate']) ? $data['startdate'] : Carbon::now()->format('Y-m-d');
        $data['targetdate']    = isset($data['targetdate']) && $data['targetdate'] != '' ? $data['targetdate'] : null;
        $data['account_id']    = intval($data['account_id']);


        if ($data['remind_me'] == 0) {
            $data['reminder'] = null;
        }


        $repeated = new \Piggybank($data);
        if (!$repeated->isValid()) {
            var_dump($repeated->getErrors()->all());
            exit;
        }
        $repeated->save();

        return $repeated;
    }

    /**
     * @param \Eloquent $model
     * @param array     $data
     *
     * @return bool
     * @throws NotImplementedException
     */
    public function update(\Eloquent $model, array $data)
    {
        // TODO: Implement update() method.
        throw new NotImplementedException;
    }

    /**
     * Validates an array. Returns an array containing MessageBags
     * errors/warnings/successes.
     *
     * @param array $model
     *
     * @return array
     */
    public function validate(array $model)
    {
        $warnings  = new MessageBag;
        $successes = new MessageBag;
        $errors    = new MessageBag;

        /*
         * Name validation:
         */
        if (!isset($model['name'])) {
            $errors->add('name', 'Name is mandatory');
        }

        if (isset($model['name']) && strlen($model['name']) == 0) {
            $errors->add('name', 'Name is too short');
        }
        if (isset($model['name']) && strlen($model['name']) > 100) {
            $errors->add('name', 'Name is too long');
        }

        if (intval($model['account_id']) == 0) {
            $errors->add('account_id', 'Account is mandatory');
        }
        if ($model['targetdate'] == '' && isset($model['remind_me']) && intval($model['remind_me']) == 1) {
            $errors->add('targetdate', 'Target date is mandatory when setting reminders.');
        }
        if ($model['targetdate'] != '') {
            try {
                new Carbon($model['targetdate']);
            } catch (\Exception $e) {
                $errors->add('targetdate', 'Invalid date.');
            }
            $diff = Carbon::now()->diff(new Carbon($model['targetdate']));
            if ($diff->days > 365) {
                $errors->add('targetdate', 'First target date should a a year or less from now.');
            }
        } else {
            $errors->add('targetdate', 'Invalid target date.');
        }
        if (floatval($model['targetamount']) < 0.01) {
            $errors->add('targetamount', 'Amount should be above 0.01.');
        }
        if (!in_array(ucfirst($model['reminder']), \Config::get('firefly.piggybank_periods'))) {
            $errors->add('reminder', 'Invalid reminder period (' . $model['reminder'] . ')');
        }

        if (!in_array(ucfirst($model['rep_length']), \Config::get('firefly.piggybank_periods'))) {
            $errors->add('rep_length', 'Invalid repeat period (' . $model['rep_length'] . ')');
        }

        // check period.
        if (!$errors->has('reminder') && !$errors->has('targetdate') && isset($model['remind_me']) && intval($model['remind_me']) == 1) {
            $today  = new Carbon;
            $target = new Carbon($model['targetdate']);
            switch ($model['reminder']) {
                case 'week':
                    $today->addWeek();
                    break;
                case 'month':
                    $today->addMonth();
                    break;
                case 'year':
                    $today->addYear();
                    break;
            }
            if ($today > $target) {
                $errors->add('reminder', 'Target date is too close to today to set reminders.');
            }
        }

        $validator = \Validator::make($model, \Piggybank::$rules);
        if ($validator->invalid()) {
            $errors->merge($errors);
        }

        // add ok messages.
        $list = ['name', 'account_id', 'rep_every', 'rep_times', 'rep_length', 'targetamount', 'targetdate', 'remind_me', 'reminder'];
        foreach ($list as $entry) {
            if (!$errors->has($entry) && !$warnings->has($entry)) {
                $successes->add($entry, 'OK');
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'successes' => $successes];
    }

    /**
     * Returns an object with id $id.
     *
     * @param int $id
     *
     * @return \Eloquent
     * @throws NotImplementedException
     */
    public function find($id)
    {
        // TODO: Implement find() method.
        throw new NotImplementedException;
    }

    /**
     * Finds an account type using one of the "$what"'s: expense, asset, revenue, opening, etc.
     *
     * @param $what
     *
     * @return \AccountType|null
     * @throws NotImplementedException
     */
    public function findByWhat($what)
    {
        // TODO: Implement findByWhat() method.
        throw new NotImplementedException;
    }

    /**
     * Returns all objects.
     *
     * @return Collection
     */
    public function get()
    {
        return $this->getUser()->piggybanks()->where('repeats', 1)->get();
    }

    /**
     * @param array $ids
     *
     * @return Collection
     * @throws NotImplementedException
     */
    public function getByIds(array $ids)
    {
        // TODO: Implement getByIds() method.
        throw new NotImplementedException;
    }

    /**
     * @param \Account $account
     *
     * @return float
     * @throws NotImplementedException
     */
    public function leftOnAccount(\Account $account)
    {
        // TODO: Implement leftOnAccount() method.
        throw new NotImplementedException;
    }
}