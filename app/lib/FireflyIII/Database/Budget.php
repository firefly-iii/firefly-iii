<?php
namespace FireflyIII\Database;

use Carbon\Carbon;
use Illuminate\Support\MessageBag;
use LaravelBook\Ardent\Ardent;
use Illuminate\Support\Collection;
use FireflyIII\Database\Ifaces\CommonDatabaseCalls;
use FireflyIII\Database\Ifaces\CUD;
use FireflyIII\Database\Ifaces\BudgetInterface;
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
     * @param Ardent $model
     *
     * @return bool
     */
    public function destroy(Ardent $model)
    {
        // TODO: Implement destroy() method.
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
        // TODO: Implement validate() method.
    }

    /**
     * @param array $data
     *
     * @return Ardent
     */
    public function store(array $data)
    {
        // TODO: Implement store() method.
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
     * @param \Budget $budget
     * @param Carbon $date
     * @return float
     */
    public function spentInMonth(\Budget $budget, Carbon $date) {
        $end = clone $date;
        $date->startOfMonth();
        $end->endOfMonth();
        $sum = floatval($budget->transactionjournals()->before($end)->after($date)->lessThan(0)->sum('amount')) * -1;
        return $sum;
    }

    /**
     * @param array $ids
     *
     * @return Collection
     */
    public function getByIds(array $ids)
    {
        // TODO: Implement getByIds() method.
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
                $query->select('transaction_journals.id')->from('transaction_journals')
                      ->leftJoin(
                          'component_transaction_journal', 'component_transaction_journal.transaction_journal_id', '=',
                          'transaction_journals.id'
                      )
                      ->leftJoin('components', 'components.id', '=', 'component_transaction_journal.component_id')
                      ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                      ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                      ->where('components.class', 'Budget');
            }
        )->before($end)->after($start)->lessThan(0)->transactionTypes(['Withdrawal'])->get();
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
    }
}