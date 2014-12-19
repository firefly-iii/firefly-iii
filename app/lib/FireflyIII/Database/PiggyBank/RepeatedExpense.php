<?php

namespace FireflyIII\Database\PiggyBank;


use Carbon\Carbon;
use FireflyIII\Collection\PiggybankPart;
use FireflyIII\Database\CommonDatabaseCalls;
use FireflyIII\Database\CUD;
use FireflyIII\Database\SwitchUser;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;


/**
 * Class RepeatedExpense
 *
 * @package FireflyIII\Database
 */
class RepeatedExpense implements CUD, CommonDatabaseCalls, PiggyBankInterface
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
     * @return Collection
     */
    public function calculateParts(\PiggybankRepetition $repetition)
    {
        /** @var \Piggybank $piggyBank */
        $piggyBank    = $repetition->piggybank()->first();
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
            $currentTarget = \DateKit::endOfX($currentStart, $piggyBank->reminder, $repetition->targetdate);
            $entry         = ['repetition'       => $repetition, 'amountPerBar' => null, 'currentAmount' => floatval($repetition->currentamount),
                              'cumulativeAmount' => null, 'startDate' => $currentStart, 'targetDate' => $currentTarget];
            $bars->push($this->createPiggyBankPart($entry));
            $currentStart = clone $currentTarget;
            $currentStart->addDay();

        }
        $amountPerBar = floatval($piggyBank->targetamount) / $bars->count();
        $cumulative   = $amountPerBar;
        /** @var PiggybankPart $bar */
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
     * @return PiggybankPart
     */
    public function createPiggyBankPart(array $data)
    {
        $part = new PiggybankPart;
        $part->setRepetition($data['repetition']);
        $part->setAmountPerBar($data['amountPerBar']);
        $part->setCurrentamount($data['currentAmount']);
        $part->setCumulativeAmount($data['cumulativeAmount']);
        $part->setStartdate($data['startDate']);
        $part->setTargetdate($data['targetDate']);

        return $part;
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

        $data['rep_every']     = intval($data['rep_every']);
        $data['reminder_skip'] = intval($data['reminder_skip']);
        $data['order']         = intval($data['order']);
        $data['remind_me']     = intval($data['remind_me']);
        $data['account_id']    = intval($data['account_id']);


        if ($data['remind_me'] == 0) {
            $data['reminder'] = null;
        }

        $repeated = new \Piggybank($data);
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
     *
     * ignored because this method will be gone soon.
     * @SuppressWarnings("Cyclomatic")
     * @SuppressWarnings("NPath")
     * @SuppressWarnings("MethodLength")
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