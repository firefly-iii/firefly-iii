<?php
namespace FireflyIII\Database;

use Carbon\Carbon;
use FireflyIII\Database\Ifaces\CategoryInterface;
use FireflyIII\Database\Ifaces\CommonDatabaseCalls;
use FireflyIII\Database\Ifaces\CUD;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use LaravelBook\Ardent\Ardent;

/**
 * Class Category
 *
 * @package FireflyIII\Database
 */
class Category implements CUD, CommonDatabaseCalls, CategoryInterface
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

        return true;
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
        $model->name = $data['name'];
        if (!$model->validate()) {
            var_dump($model->errors()->all());
            exit;
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

        if (isset($model['name'])) {
            if (strlen($model['name']) < 1) {
                $errors->add('name', 'Name is too short');
            }
            if (strlen($model['name']) > 200) {
                $errors->add('name', 'Name is too long');

            }
        } else {
            $errors->add('name', 'Name is mandatory');
        }
        $validator = \Validator::make($model, \Component::$rules);

        if ($validator->invalid()) {
            $errors->merge($validator->errors());
        }


        if (!$errors->has('name')) {
            $successes->add('name', 'OK');
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
        return $this->getUser()->categories()->orderBy('name', 'ASC')->get();
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
     * @param \Category $budget
     * @param Carbon    $date
     *
     * @return null
     */
    public function repetitionOnStartingOnDate(\Category $category, Carbon $date)
    {
        return null;
    }

    /**
     * @param \Category $category
     * @param Carbon    $date
     *
     * @return float
     */
    public function spentInMonth(\Category $category, Carbon $date)
    {
        $end = clone $date;
        $date->startOfMonth();
        $end->endOfMonth();
        $sum = floatval($category->transactionjournals()->before($end)->after($date)->lessThan(0)->sum('amount')) * -1;

        return $sum;
    }
}