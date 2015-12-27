<?php

namespace FireflyIII\Repositories\Budget;

use Auth;
use Carbon\Carbon;
use DB;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Shared\ComponentRepository;
use FireflyIII\Support\CacheProperties;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Input;

/**
 * Class BudgetRepository
 *
 * @package FireflyIII\Repositories\Budget
 */
class BudgetRepository extends ComponentRepository implements BudgetRepositoryInterface
{

    /**
     * @return void
     */
    public function cleanupBudgets()
    {
        // delete limits with amount 0:
        BudgetLimit::where('amount', 0)->delete();

    }

    /**
     * @param Budget $budget
     *
     * @return boolean
     */
    public function destroy(Budget $budget)
    {
        $budget->delete();

        return true;
    }

    /**
     * @param Budget $budget
     * @param Carbon $date
     *
     * @return float
     */
    public function expensesOnDay(Budget $budget, Carbon $date)
    {
        bcscale(2);
        $sum = $budget->transactionjournals()->transactionTypes([TransactionType::WITHDRAWAL])->onDate($date)->get(['transaction_journals.*'])->sum('amount');

        return $sum;
    }

    /**
     * @return Collection
     */
    public function getActiveBudgets()
    {
        /** @var Collection $set */
        $set = Auth::user()->budgets()->where('active', 1)->get();

        $set = $set->sortBy(
            function (Budget $budget) {
                return strtolower($budget->name);
            }
        );

        return $set;
    }

    /**
     * Returns a list of budgets, budget limits and limit repetitions
     * (doubling any of them in a left join)
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getBudgetsAndLimitsInRange(Carbon $start, Carbon $end)
    {
        /** @var Collection $set */
        $set = Auth::user()
                   ->budgets()
                   ->leftJoin('budget_limits', 'budget_limits.budget_id', '=', 'budgets.id')
                   ->leftJoin('limit_repetitions', 'limit_repetitions.budget_limit_id', '=', 'budget_limits.id')
                   ->where(
                       function (Builder $query) use ($start, $end) {
                           $query->where(
                               function (Builder $query) use ($start, $end) {
                                   $query->where('limit_repetitions.startdate', '>=', $start->format('Y-m-d'));
                                   $query->where('limit_repetitions.startdate', '<=', $end->format('Y-m-d'));
                               }
                           );
                           $query->orWhere(
                               function (Builder $query) {
                                   $query->whereNull('limit_repetitions.startdate');
                                   $query->whereNull('limit_repetitions.enddate');
                               }
                           );
                       }
                   )
                   ->get(['budgets.*', 'limit_repetitions.startdate', 'limit_repetitions.enddate', 'limit_repetitions.amount']);

        $set = $set->sortBy(
            function (Budget $budget) {
                return strtolower($budget->name);
            }
        );

        return $set;

    }

    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getBudgetLimitRepetitions(Budget $budget, Carbon $start, Carbon $end)
    {
        /** @var Collection $repetitions */
        return LimitRepetition::
        leftJoin('budget_limits', 'limit_repetitions.budget_limit_id', '=', 'budget_limits.id')
                              ->where('limit_repetitions.startdate', '<=', $end->format('Y-m-d 00:00:00'))
                              ->where('limit_repetitions.startdate', '>=', $start->format('Y-m-d 00:00:00'))
                              ->where('budget_limits.budget_id', $budget->id)
                              ->get(['limit_repetitions.*']);
    }

    /**
     * @param Budget $budget
     *
     * @return Collection
     */
    public function getBudgetLimits(Budget $budget)
    {
        return $budget->budgetLimits()->orderBy('startdate', 'DESC')->get();
    }

    /**
     * @return Collection
     */
    public function getBudgets()
    {
        /** @var Collection $set */
        $set = Auth::user()->budgets()->get();

        $set = $set->sortBy(
            function (Budget $budget) {
                return strtolower($budget->name);
            }
        );

        return $set;
    }

    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return LimitRepetition|null
     */
    public function getCurrentRepetition(Budget $budget, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties;
        $cache->addProperty($budget->id);
        $cache->addProperty($start);
        $cache->addProperty($end);

        $cache->addProperty('getCurrentRepetition');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $data = $budget->limitrepetitions()
                       ->where('limit_repetitions.startdate', $start->format('Y-m-d 00:00:00'))
                       ->where('limit_repetitions.enddate', $end->format('Y-m-d 00:00:00'))
                       ->first(['limit_repetitions.*']);
        //Log::debug('Looking for limit reps for budget #' . $budget->id . ' start [' . $start . '] and end [' . $end . '].');
        //Log::debug(DB::getQueryLog())
        $cache->store($data);

        return $data;
    }

    /**
     * @param Budget $budget
     *
     * @return Carbon
     */
    public function getFirstBudgetLimitDate(Budget $budget)
    {
        $limit = $budget->budgetlimits()->orderBy('startdate', 'ASC')->first();
        if ($limit) {
            return $limit->startdate;
        }

        return Carbon::now()->startOfYear();
    }

    /**
     * @return Collection
     */
    public function getInactiveBudgets()
    {
        /** @var Collection $set */
        $set = Auth::user()->budgets()->where('active', 0)->get();

        $set = $set->sortBy(
            function (Budget $budget) {
                return strtolower($budget->name);
            }
        );

        return $set;
    }

    /**
     * Returns all the transaction journals for a limit, possibly limited by a limit repetition.
     *
     * @param Budget          $budget
     * @param LimitRepetition $repetition
     * @param int             $take
     *
     * @return LengthAwarePaginator
     */
    public function getJournals(Budget $budget, LimitRepetition $repetition = null, $take = 50)
    {
        $cache = new CacheProperties;
        $cache->addProperty($budget->id);
        if ($repetition) {
            $cache->addProperty($repetition->id);
        }
        $cache->addProperty($take);
        $cache->addProperty('getJournals');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $offset     = intval(Input::get('page')) > 0 ? intval(Input::get('page')) * $take : 0;
        $setQuery   = $budget->transactionJournals()->withRelevantData()->take($take)->offset($offset)
                             ->orderBy('transaction_journals.date', 'DESC')
                             ->orderBy('transaction_journals.order', 'ASC')
                             ->orderBy('transaction_journals.id', 'DESC');
        $countQuery = $budget->transactionJournals();


        if (!is_null($repetition->id)) {
            $setQuery->after($repetition->startdate)->before($repetition->enddate);
            $countQuery->after($repetition->startdate)->before($repetition->enddate);
        }


        $set   = $setQuery->get(['transaction_journals.*']);
        $count = $countQuery->count();


        $paginator = new LengthAwarePaginator($set, $count, $take, $offset);
        $cache->store($paginator);

        return $paginator;
    }

    /**
     * @deprecated
     *
     * @param Budget $budget
     *
     * @return Carbon
     */
    public function getLastBudgetLimitDate(Budget $budget)
    {
        $limit = $budget->budgetlimits()->orderBy('startdate', 'DESC')->first();
        if ($limit) {
            return $limit->startdate;
        }

        return Carbon::now()->startOfYear();
    }

    /**
     * @deprecated
     *
     * @param Budget $budget
     * @param Carbon $date
     *
     * @return float|null
     */
    public function getLimitAmountOnDate(Budget $budget, Carbon $date)
    {
        $repetition = LimitRepetition::leftJoin('budget_limits', 'limit_repetitions.budget_limit_id', '=', 'budget_limits.id')
                                     ->where('limit_repetitions.startdate', $date->format('Y-m-d 00:00:00'))
                                     ->where('budget_limits.budget_id', $budget->id)
                                     ->first(['limit_repetitions.*']);

        if ($repetition) {
            return $repetition->amount;
        }

        return null;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getWithoutBudget(Carbon $start, Carbon $end)
    {
        return Auth::user()
                   ->transactionjournals()
                   ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                   ->whereNull('budget_transaction_journal.id')
                   ->before($end)
                   ->after($start)
                   ->orderBy('transaction_journals.date', 'DESC')
                   ->orderBy('transaction_journals.order', 'ASC')
                   ->orderBy('transaction_journals.id', 'DESC')
                   ->get(['transaction_journals.*']);
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return double
     */
    public function getWithoutBudgetSum(Carbon $start, Carbon $end)
    {
        $entry = Auth::user()
                     ->transactionjournals()
                     ->whereNotIn(
                         'transaction_journals.id', function (QueryBuilder $query) use ($start, $end) {
                         $query
                             ->select('transaction_journals.id')
                             ->from('transaction_journals')
                             ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                             ->where('transaction_journals.date', '>=', $start->format('Y-m-d 00:00:00'))
                             ->where('transaction_journals.date', '<=', $end->format('Y-m-d 00:00:00'))
                             ->whereNotNull('budget_transaction_journal.budget_id');
                     }
                     )
                     ->after($start)
                     ->before($end)
                     ->leftJoin(
                         'transactions', function (JoinClause $join) {
                         $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('transactions.amount', '<', 0);
                     }
                     )
                     ->transactionTypes([TransactionType::WITHDRAWAL])
                     ->first([DB::Raw('SUM(`transactions`.`amount`) as `journalAmount`')]);

        return $entry->journalAmount;
    }

    /**
     * @param Budget     $budget
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return string
     */
    public function balanceInPeriod(Budget $budget, Carbon $start, Carbon $end, Collection $accounts)
    {
        return $this->commonBalanceInPeriod($budget, $start, $end, $accounts);
    }

    /**
     * @param array $data
     *
     * @return Budget
     */
    public function store(array $data)
    {
        $newBudget = new Budget(
            [
                'user_id' => $data['user'],
                'name'    => $data['name'],
            ]
        );
        $newBudget->save();

        return $newBudget;
    }


    /**
     * @param Budget $budget
     * @param array  $data
     *
     * @return Budget
     */
    public function update(Budget $budget, array $data)
    {
        // update the account:
        $budget->name   = $data['name'];
        $budget->active = $data['active'];
        $budget->save();

        return $budget;
    }

    /**
     * @param Budget $budget
     * @param Carbon $date
     * @param        $amount
     *
     * @return BudgetLimit
     */
    public function updateLimitAmount(Budget $budget, Carbon $date, $amount)
    {
        // there should be a budget limit for this startdate:
        /** @var BudgetLimit $limit */
        $limit = $budget->budgetlimits()->where('budget_limits.startdate', $date)->first(['budget_limits.*']);

        if (!$limit) {
            // if not, create one!
            $limit = new BudgetLimit;
            $limit->budget()->associate($budget);
            $limit->startdate   = $date;
            $limit->amount      = $amount;
            $limit->repeat_freq = 'monthly';
            $limit->repeats     = 0;
            $limit->save();

            // likewise, there should be a limit repetition to match the end date
            // (which is always the end of the month) but that is caught by an event.

        } else {
            if ($amount > 0) {
                $limit->amount = $amount;
                $limit->save();
            } else {
                $limit->delete();
            }
        }

        return $limit;


    }
}
