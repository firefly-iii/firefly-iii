<?php
namespace FireflyIII\Database;

use Carbon\Carbon;
use FireflyIII\Database\Ifaces\BudgetInterface;
use FireflyIII\Database\Ifaces\CommonDatabaseCalls;
use FireflyIII\Database\Ifaces\CUD;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use LaravelBook\Ardent\Ardent;

/**
 * Class Budget
 *
 * @package FireflyIII\Database
 */
class Budget implements CUD, CommonDatabaseCalls, BudgetInterface
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
        $data['user_id'] = $this->getUser()->id;

        $budget        = new \Budget($data);
        $budget->class = 'Budget';

        if (!$budget->validate()) {
            var_dump($budget->errors()->all());
            exit;
        }
        $budget->save();

        return $budget;
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
        $budgets = $this->getUser()->budgets()->get();

        return $budgets;
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

    public function getTransactionJournals(\Budget $budget, $limit = 50)
    {
        $offset = intval(\Input::get('page')) > 0 ? intval(\Input::get('page')) * $limit : 0;
        $set    = $budget->transactionJournals()->withRelevantData()->take($limit)->offset($offset)->orderBy('date', 'DESC')->get(['transaction_journals.*']);
        $count  = $budget->transactionJournals()->count();
        $items  = [];
        foreach ($set as $entry) {
            $items[] = $entry;
        }

        return \Paginator::make($items, $count, $limit);

    }

    public function getTransactionJournalsInRepetition(\Budget $budget, \LimitRepetition $repetition, $limit = 50)
    {
        $start = $repetition->startdate;
        $end = $repetition->enddate;

        $offset = intval(\Input::get('page')) > 0 ? intval(\Input::get('page')) * $limit : 0;
        $set    = $budget->transactionJournals()->withRelevantData()->before($end)->after($start)->take($limit)->offset($offset)->orderBy('date', 'DESC')->get(['transaction_journals.*']);
        $count  = $budget->transactionJournals()->before($end)->after($start)->count();
        $items  = [];
        foreach ($set as $entry) {
            $items[] = $entry;
        }

        return \Paginator::make($items, $count, $limit);
    }

    /**
     * @param \Budget $budget
     * @param Carbon  $date
     *
     * @return \LimitRepetition|null
     */
    public function repetitionOnStartingOnDate(\Budget $budget, Carbon $date)
    {
        return \LimitRepetition::
        leftJoin('limits', 'limit_repetitions.limit_id', '=', 'limits.id')->leftJoin(
            'components', 'limits.component_id', '=', 'components.id'
        )->where('limit_repetitions.startdate', $date->format('Y-m-d'))->where(
            'components.id', $budget->id
        )->first(['limit_repetitions.*']);
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function transactionsWithoutBudgetInDateRange(Carbon $start, Carbon $end)
    {
        // Add expenses that have no budget:
        return \Auth::user()->transactionjournals()->whereNotIn(
            'transaction_journals.id', function ($query) use ($start, $end) {
                $query->select('transaction_journals.id')->from('transaction_journals')->leftJoin(
                    'component_transaction_journal', 'component_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id'
                )->leftJoin('components', 'components.id', '=', 'component_transaction_journal.component_id')->where(
                    'transaction_journals.date', '>=', $start->format('Y-m-d')
                )->where('transaction_journals.date', '<=', $end->format('Y-m-d'))->where('components.class', 'Budget');
            }
        )->before($end)->after($start)->lessThan(0)->transactionTypes(['Withdrawal'])->get();
    }

    /**
     * @param \Budget $budget
     * @param Carbon  $date
     *
     * @return float
     */
    public function spentInMonth(\Budget $budget, Carbon $date)
    {
        $end = clone $date;
        $date->startOfMonth();
        $end->endOfMonth();
        $sum = floatval($budget->transactionjournals()->before($end)->after($date)->lessThan(0)->sum('amount')) * -1;

        return $sum;
    }
}