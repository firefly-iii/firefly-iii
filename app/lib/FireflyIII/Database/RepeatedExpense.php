<?php

namespace FireflyIII\Database;


use Carbon\Carbon;
use FireflyIII\Collection\PiggybankPart;
use FireflyIII\Database\Ifaces\CommonDatabaseCalls;
use FireflyIII\Database\Ifaces\CUD;
use FireflyIII\Database\Ifaces\PiggybankInterface;
use FireflyIII\Exception\FireflyException;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use LaravelBook\Ardent\Ardent;

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
     * @return \PiggybankRepetition
     */
    public function calculateParts(\PiggybankRepetition $repetition)
    {
        \Log::debug('NOW in calculateParts()');
        \Log::debug('Repetition id is ' . $repetition->id);
        /** @var \Piggybank $piggyBank */
        $piggyBank = $repetition->piggybank()->first();
        \Log::debug('connected piggy bank is: ' . $piggyBank->name . ' (#' . $piggyBank->id . ')');

        /*
         * If no reminders are set, the repetition is split in exactly one part:
         */
        if (is_null($piggyBank->reminder)) {
            $parts = 1;
        } else {
            /*
             * Number of parts is the number of [reminder period]s between
             * the start date and the target date
             */
            /** @var Carbon $start */
            $start  = clone $repetition->startdate;
            /** @var Carbon $target */
            $target = clone $repetition->targetdate;

            switch ($piggyBank->reminder) {
                default:
                    throw new FireflyException('Cannot handle "' . $piggyBank->reminder . '" reminders for repeated expenses (calculateParts)');
                    break;
                case 'week':
                    $parts = $start->diffInWeeks($target);
                    break;
                case 'month':
                    $parts = $start->diffInMonths($target);
                    break;
                case 'quarter':
                    $parts = ceil($start->diffInMonths($target) / 3);
                    break;
                case 'year':
                    $parts = $start->diffInYears($target);
                    break;
            }
            $parts = $parts < 1 ? 1 : $parts;
            unset($start, $target);


            //            /*
            //             * Otherwise, FF3 splits by the difference in time and the amount
            //             * of reminders the user wants.
            //             */
            //            switch ($piggyBank->reminder) {
            //                default:
            //                    throw new FireflyException('Cannot handle "' . $piggyBank->reminder . '" reminders for repeated expenses (calculateParts)');
            //                    break;
            //                case 'week':
            //                    $start = clone $repetition->startdate;
            //                    $start->startOfWeek();
            //                    $end = clone $repetition->targetdate;
            //                    $end->endOfWeek();
            //                    $parts = $start->diffInWeeks($end);
            //                    unset($start, $end);
            //                    break;
            //                case 'month':
            //                    $start = clone $repetition->startdate;
            //                    $start->startOfMonth();
            //                    $end = clone $repetition->targetdate;
            //                    $end->endOfMonth();
            //                    $parts = $start->diffInMonths($end);
            //                    unset($start, $end);
            //                    break;
            //            }
        }
        $amountPerBar  = floatval($piggyBank->targetamount) / $parts;
        $currentAmount = floatval($amountPerBar);
        $currentStart  = clone $repetition->startdate;
        $currentTarget = clone $repetition->targetdate;
        $bars          = new Collection;

        //        if($parts > 12) {
        //            $parts = 12;
        //            $currentStart = \DateKit::startOfPeriod(Carbon::now(), $piggyBank->reminder);
        //            $currentEnd = \DateKit::endOfPeriod($currentEnd, $piggyBank->reminder);
        //        }

        for ($i = 0; $i < $parts; $i++) {
            /*
             * Jump one month ahead after the first instance:
             */
            //            if ($i > 0) {
            //                $currentStart = \DateKit::addPeriod($currentStart, $piggyBank->reminder, 0);
            //                /*
            //                 * Jump to the start of the period too:
            //                 */
            //                $currentStart = \DateKit::startOfPeriod($currentStart, $piggyBank->reminder);
            //
            //            }


            /*
             * Move the current start to the actual start of
             * the [period] once the first iteration has passed.
             */
            //            if ($i != 0) {
            //                $currentStart = \DateKit::startOfPeriod($currentStart, $piggyBank->reminder);
            //            }
            //            if($i == 0 && !is_null($piggyBank->reminder)) {
            //                $currentEnd = \DateKit::startOfPeriod($currentStart, $piggyBank->reminder);
            //                $currentEnd = \DateKit::endOfPeriod($currentEnd, $piggyBank->reminder);
            //            }

            $part = new PiggybankPart;
            $part->setRepetition($repetition);
            $part->setAmount($currentAmount);
            $part->setAmountPerBar($amountPerBar);
            $part->setCurrentamount($repetition->currentamount);
            $part->setStartdate($currentStart);
            $part->setTargetdate($currentTarget);

            //            if (!is_null($piggyBank->reminder)) {
            //                $currentStart = \DateKit::addPeriod($currentStart, $piggyBank->reminder, 0);
            //                $currentEnd   = \DateKit::endOfPeriod($currentStart, $piggyBank->reminder);
            //            }


            $bars->push($part);
            $currentAmount += $amountPerBar;
        }
        $repetition->bars = $bars;

        return $repetition;
        exit;


        $repetition->hello = 'World!';

        return $repetition;

        $return      = new Collection;
        $repetitions = $piggyBank->piggybankrepetitions()->get();
        /** @var \PiggybankRepetition $repetition */
        foreach ($repetitions as $repetition) {


            if (is_null($piggyBank->reminder)) {
                // simple, one part "repetition".
                $part = new PiggybankPart;
                $part->setRepetition($repetition);
            } else {
                $part = new PiggybankPart;
            }


            // end!
            $return->push($part);
        }

        exit;

        return $return;
        $piggyBank->currentRelevantRep(); // get the current relevant repetition.
        if (!is_null($piggyBank->reminder)) {
            switch ($piggyBank->reminder) {
                default:
                    throw new FireflyException('Cannot handle "' . $piggyBank->reminder . '" reminders for repeated expenses');
                    break;
                case 'month':
                    $start = clone $piggyBank->currentRep->startdate;
                    $start->startOfMonth();
                    $end = clone $piggyBank->currentRep->targetdate;
                    $end->endOfMonth();
                    $piggyBank->parts = $start->diffInMonths($end);
                    unset($start, $end);
                    break;
            }

        } else {
            $piggyBank->parts = 1;
        }

        // number of bars:
        $piggyBank->barCount = floor(12 / $piggyBank->parts) == 0 ? 1 : floor(12 / $piggyBank->parts);
        $amountPerBar        = floatval($piggyBank->targetamount) / $piggyBank->parts;
        $currentAmount       = floatval($amountPerBar);
        $bars                = [];
        $currentStart        = clone $piggyBank->currentRep->startdate;
        for ($i = 0; $i < $piggyBank->parts; $i++) {
            // niet elke keer een andere dinges pakken? om target te redden?
            if (!is_null($piggyBank->reminder)) {
                $currentStart = \DateKit::addPeriod($currentStart, $piggyBank->reminder, 0);
            }
            $bars[] = [
                'amount' => $currentAmount,
                'date'   => $currentStart
            ];


            $currentAmount += $amountPerBar;
        }
        $piggyBank->bars = $bars;
    }

    /**
     * @param Ardent $model
     *
     * @return bool
     */
    public function destroy(Ardent $model)
    {
        // TODO: Implement destroy() method.
        throw new NotImplementedException;
    }

    /**
     * @param array $data
     *
     * @return Ardent
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
        if (!$repeated->validate()) {
            var_dump($repeated->errors()->all());
            exit;
        }
        $repeated->save();

        return $repeated;
    }

    /**
     * @param Ardent $model
     * @param array  $data
     *
     * @return bool
     */
    public function update(Ardent $model, array $data)
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
     * @return Ardent
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
     */
    public function leftOnAccount(\Account $account)
    {
        // TODO: Implement leftOnAccount() method.
        throw new NotImplementedException;
    }
}