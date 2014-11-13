<?php

namespace FireflyIII\Database;


use Carbon\Carbon;
use FireflyIII\Database\Ifaces\CommonDatabaseCalls;
use FireflyIII\Database\Ifaces\CUD;
use FireflyIII\Database\Ifaces\RecurringInterface;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use LaravelBook\Ardent\Ardent;

/**
 * Class Recurring
 *
 * @package FireflyIII\Database
 */
class Recurring implements CUD, CommonDatabaseCalls, RecurringInterface
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
        // TODO: Implement store() method.
        throw new NotImplementedException;
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

        if (isset($model['name']) && strlen($model['name']) == 0) {
            $errors->add('name', 'Name must be longer.');
        }
        if (isset($model['name']) && strlen($model['name']) > 200) {
            $errors->add('name', 'Name must be shorter.');
        }

        if (isset($model['match']) && strlen(trim($model['match'])) <= 2) {
            $errors->add('match', 'Needs more matches.');
        }

        if (isset($model['amount_min']) && floatval($model['amount_min']) < 0.01) {
            $errors->add('amount_min', 'Minimum amount must be higher.');
        }
        if (isset($model['amount_max']) && floatval($model['amount_max']) < 0.02) {
            $errors->add('amount_max', 'Maximum amount must be higher.');
        }
        if(isset($model['amount_min']) && isset($model['amount_max']) && floatval($model['amount_min']) > floatval($model['amount_max'])) {
            $errors->add('amount_max', 'Maximum amount can not be less than minimum amount.');
            $errors->add('amount_min', 'Minimum amount can not be more than maximum amount.');
        }

        if ($model['date'] != '') {
            try {
                new Carbon($model['date']);
            } catch (\Exception $e) {
                $errors->add('date', 'Invalid date.');
            }
        }

        $reminders = \Config::get('firefly.budget_periods');
        if (!isset($model['repeat_freq']) || (isset($model['repeat_freq']) && !in_array($model['repeat_freq'], $reminders))) {
            $errors->add('repeat_freq', 'Invalid reminder period');
        }

        if (isset($model['skip']) && intval($model['skip']) < 0) {
            $errors->add('skip', 'Invalid skip.');
        }

        $set = ['name','match','amount_min','amount_max','date','repeat_freq','skip','automatch','active'];
        foreach($set as $entry) {
            if(!$errors->has($entry)) {
                $successes->add($entry,'OK');
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
        return $this->getUser()->recurringtransactions()->get();
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
     * @param \RecurringTransaction $recurring
     * @param Carbon                $start
     * @param Carbon                $end
     *
     * @return \TransactionJournal|null
     */
    public function getJournalForRecurringInRange(\RecurringTransaction $recurring, Carbon $start, Carbon $end)
    {
        return $this->getUser()->transactionjournals()->where('recurring_transaction_id', $recurring->id)->after($start)->before($end)->first();

    }
}