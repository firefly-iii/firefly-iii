<?php

namespace FireflyIII\Database;


use Carbon\Carbon;
use FireflyIII\Database\Ifaces\CommonDatabaseCalls;
use FireflyIII\Database\Ifaces\CUD;
use FireflyIII\Database\Ifaces\PiggybankInterface;
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