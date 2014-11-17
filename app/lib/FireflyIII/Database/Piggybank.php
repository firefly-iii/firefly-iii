<?php
namespace FireflyIII\Database;

use Carbon\Carbon;
use Firefly\Exception\FireflyException;
use FireflyIII\Database\Ifaces\CommonDatabaseCalls;
use FireflyIII\Database\Ifaces\CUD;
use FireflyIII\Database\Ifaces\PiggybankInterface;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use LaravelBook\Ardent\Ardent;

/**
 * Class Piggybank
 *
 * @package FireflyIII\Database
 */
class Piggybank implements CUD, CommonDatabaseCalls, PiggybankInterface
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
        $model->delete();
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


        $piggybank = new \Piggybank($data);
        if (!$piggybank->validate()) {
            var_dump($piggybank->errors()->all());
            exit;
        }
        $piggybank->save();

        return $piggybank;
    }

    /**
     * @param Ardent $model
     * @param array  $data
     *
     * @return bool
     */
    public function update(Ardent $model, array $data)
    {
        /** @var \Piggybank $model */
        $model->name          = $data['name'];
        $model->account_id    = intval($data['account_id']);
        $model->targetamount  = floatval($data['targetamount']);
        $model->targetdate    = isset($data['targetdate']) && $data['targetdate'] != '' ? $data['targetdate'] : null;
        $model->rep_every     = isset($data['rep_every']) ? $data['rep_every'] : 0;
        $model->reminder_skip = isset($data['reminder_skip']) ? $data['reminder_skip'] : 0;
        $model->order         = isset($data['order']) ? $data['order'] : 0;
        $model->remind_me     = isset($data['remind_me']) ? intval($data['remind_me']) : 0;
        $model->reminder      = isset($data['reminder']) ? $data['reminder'] : 'month';
        if (!$model->validate()) {
            var_dump($model->errors());
            exit();
        }

        $model->save();

        return true;
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
                $errors->add('date', 'Invalid date.');
            }
        }
        if (floatval($model['targetamount']) < 0.01) {
            $errors->add('targetamount', 'Amount should be above 0.01.');
        }
        if (!in_array(ucfirst($model['reminder']), \Config::get('firefly.piggybank_periods'))) {
            $errors->add('reminder', 'Invalid reminder period (' . $model['reminder'] . ')');
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
        $list = ['name', 'account_id', 'targetamount', 'targetdate', 'remind_me', 'reminder'];
        foreach ($list as $entry) {
            if (!$errors->has($entry) && !$warnings->has($entry)) {
                $successes->add($entry, 'OK');
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'successes' => $successes];
    }

    /**
     * Validates a model. Returns an array containing MessageBags
     * errors/warnings/successes.
     *
     * @param Ardent $model
     *
     * @return array
     */
    public function validateObject(Ardent $model)
    {
        // TODO: Implement validateObject() method.
        throw new NotImplementedException;
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
        return \Piggybank::
        leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where('piggybanks.id', '=', $id)->where('accounts.user_id', $this->getUser()->id)
                         ->first(['piggybanks.*']);
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
        return $this->getUser()->piggybanks()->where('repeats', 0)->get();
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

    public function findRepetitionByDate(\Piggybank $piggybank, Carbon $date)
    {
        $reps = $piggybank->piggybankrepetitions()->get();
        if ($reps->count() == 1) {
            return $reps->first();
        }
        if ($reps->count() == 0) {
            throw new FireflyException('Should always find a piggy bank repetition.');
        }
        throw new NotImplementedException;
    }

    /**
     * @param \Account $account
     *
     * @return float
     */
    public function leftOnAccount(\Account $account)
    {
        $balance = $account->balance();
        /** @var \Piggybank $p */
        foreach ($account->piggybanks()->get() as $p) {
            $balance -= $p->currentRelevantRep()->currentamount;
        }

        return $balance;

    }
}