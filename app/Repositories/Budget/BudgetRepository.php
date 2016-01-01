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
     * Returns the expenses for this budget grouped per day, with the date
     * in "date" (a string, not a Carbon) and the amount in "dailyAmount".
     *
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getExpensesPerDay(Budget $budget, Carbon $start, Carbon $end)
    {
        $set = Auth::user()->budgets()
                   ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.budget_id', '=', 'budgets.id')
                   ->leftJoin('transaction_journals', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                   ->leftJoin('transactions', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                   ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                   ->whereNull('transaction_journals.deleted_at')
                   ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                   ->where('budgets.id', $budget->id)
                   ->where('transactions.amount', '<', 0)
                   ->groupBy('transaction_journals.date')
                   ->orderBy('transaction_journals.date')
                   ->get(['transaction_journals.date', DB::Raw('SUM(`transactions`.`amount`) as `dailyAmount`')]);

        return $set;
    }

    /**
     * Returns the expenses for this budget grouped per month, with the date
     * in "dateFormatted" (a string, not a Carbon) and the amount in "dailyAmount".
     *
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getExpensesPerMonth(Budget $budget, Carbon $start, Carbon $end)
    {
        $set = Auth::user()->budgets()
                   ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.budget_id', '=', 'budgets.id')
                   ->leftJoin('transaction_journals', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                   ->leftJoin('transactions', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                   ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                   ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                   ->whereNull('transaction_journals.deleted_at')
                   ->where('budgets.id', $budget->id)
                   ->where('transactions.amount', '<', 0)
                   ->groupBy('dateFormatted')
                   ->orderBy('transaction_journals.date')
                   ->get(
                       [
                           DB::Raw('DATE_FORMAT(`transaction_journals`.`date`, "%Y-%m") AS `dateFormatted`'),
                           DB::Raw('SUM(`transactions`.`amount`) as `monthlyAmount`')
                       ]
                   );

        return $set;
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
     *
     * @return Carbon
     */
    public function firstActivity(Budget $budget)
    {
        $first = $budget->transactionjournals()->orderBy('date', 'ASC')->first();
        if ($first) {
            return $first->date;
        }

        return new Carbon;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getAllBudgetLimitRepetitions(Carbon $start, Carbon $end)
    {
        /** @var Collection $repetitions */
        return LimitRepetition::
        leftJoin('budget_limits', 'limit_repetitions.budget_limit_id', '=', 'budget_limits.id')
                              ->leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                              ->where('limit_repetitions.startdate', '<=', $end->format('Y-m-d 00:00:00'))
                              ->where('limit_repetitions.startdate', '>=', $start->format('Y-m-d 00:00:00'))
                              ->where('budgets.user_id', Auth::user()->id)
                              ->get(['limit_repetitions.*', 'budget_limits.budget_id']);
    }

    /**
     * Returns an array with the following key:value pairs:
     *
     * yyyy-mm-dd:<amount>
     *
     * Where yyyy-mm-dd is the date and <amount> is the money spent using DEPOSITS in the $budget
     * from all the users accounts.
     *
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function spentPerDay(Budget $budget, Carbon $start, Carbon $end)
    {
        /** @var Collection $query */
        $query = $budget->transactionJournals()
                        ->transactionTypes([TransactionType::WITHDRAWAL])
                        ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                        ->where('transactions.amount', '<', 0)
                        ->before($end)
                        ->after($start)
                        ->groupBy('dateFormatted')->get(['transaction_journals.date as dateFormatted', DB::Raw('SUM(`transactions`.`amount`) AS `sum`')]);

        $return = [];
        foreach ($query->toArray() as $entry) {
            $return[$entry['dateFormatted']] = $entry['sum'];
        }

        return $return;
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
     * Returns an array with every budget in it and the expenses for each budget
     * per month.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function getBudgetsAndExpensesPerMonth(Collection $accounts, Carbon $start, Carbon $end)
    {
        $ids = $accounts->pluck('id')->toArray();

        /** @var Collection $set */
        $set = Auth::user()->budgets()
                   ->leftJoin('budget_transaction_journal', 'budgets.id', '=', 'budget_transaction_journal.budget_id')
                   ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'budget_transaction_journal.transaction_journal_id')
                   ->leftJoin(
                       'transactions', function (JoinClause $join) {
                       $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('transactions.amount', '<', 0);
                   }
                   )
                   ->groupBy('budgets.id')
                   ->groupBy('dateFormatted')
                   ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                   ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                   ->whereIn('transactions.account_id', $ids)
                   ->get(
                       [
                           'budgets.*',
                           DB::Raw('DATE_FORMAT(`transaction_journals`.`date`, "%Y-%m") AS `dateFormatted`'),
                           DB::Raw('SUM(`transactions`.`amount`) AS `sumAmount`')
                       ]
                   );

        $set = $set->sortBy(
            function (Budget $budget) {
                return strtolower($budget->name);
            }
        );

        $return = [];
        foreach ($set as $budget) {
            $id = $budget->id;
            if (!isset($return[$id])) {
                $return[$id] = [
                    'budget'  => $budget,
                    'entries' => [],
                ];
            }
            // store each entry:
            $return[$id]['entries'][$budget->dateFormatted] = $budget->sumAmount;
        }

        return $return;
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

    /**
     * Get the budgeted amounts for each budgets in each year.
     *
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function getBudgetedPerYear(Collection $budgets, Carbon $start, Carbon $end)
    {
        $budgetIds = $budgets->pluck('id')->toArray();

        $set = Auth::user()->budgets()
                   ->leftJoin('budget_limits', 'budgets.id', '=', 'budget_limits.budget_id')
                   ->leftJoin('limit_repetitions', 'limit_repetitions.budget_limit_id', '=', 'budget_limits.id')
                   ->where('limit_repetitions.startdate', '>=', $start->format('Y-m-d'))
                   ->where('limit_repetitions.enddate', '<=', $end->format('Y-m-d'))
                   ->groupBy('budgets.id')
                   ->groupBy('dateFormatted')
                   ->whereIn('budgets.id', $budgetIds)
                   ->get(
                       [
                           'budgets.*',
                           DB::Raw('DATE_FORMAT(`limit_repetitions`.`startdate`,"%Y") as `dateFormatted`'),
                           DB::Raw('SUM(`limit_repetitions`.`amount`) as `budgeted`')
                       ]
                   );

        return $set;
    }

    /**
     * Returns an array with every budget in it and the expenses for each budget
     * per year for.
     *
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function getBudgetsAndExpensesPerYear(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end)
    {
        $ids       = $accounts->pluck('id')->toArray();
        $budgetIds = $budgets->pluck('id')->toArray();

        /** @var Collection $set */
        $set = Auth::user()->budgets()
                   ->leftJoin('budget_transaction_journal', 'budgets.id', '=', 'budget_transaction_journal.budget_id')
                   ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'budget_transaction_journal.transaction_journal_id')
                   ->leftJoin(
                       'transactions', function (JoinClause $join) {
                       $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('transactions.amount', '<', 0);
                   }
                   )
                   ->groupBy('budgets.id')
                   ->groupBy('dateFormatted')
                   ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                   ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                   ->whereIn('transactions.account_id', $ids)
                   ->whereIn('budgets.id', $budgetIds)
                   ->get(
                       [
                           'budgets.*',
                           DB::Raw('DATE_FORMAT(`transaction_journals`.`date`, "%Y") AS `dateFormatted`'),
                           DB::Raw('SUM(`transactions`.`amount`) AS `sumAmount`')
                       ]
                   );

        $set = $set->sortBy(
            function (Budget $budget) {
                return strtolower($budget->name);
            }
        );

        $return = [];
        foreach ($set as $budget) {
            $id = $budget->id;
            if (!isset($return[$id])) {
                $return[$id] = [
                    'budget'  => $budget,
                    'entries' => [],
                ];
            }
            // store each entry:
            $return[$id]['entries'][$budget->dateFormatted] = $budget->sumAmount;
        }

        return $return;
    }

    /**
     * Returns an array with the following key:value pairs:
     *
     * yyyy-mm-dd:<array>
     *
     * That array contains:
     *
     * budgetid:<amount>
     *
     * Where yyyy-mm-dd is the date and <amount> is the money spent using WITHDRAWALS in the $budget
     * from the given users accounts..
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentAllPerDayForAccounts(Collection $accounts, Carbon $start, Carbon $end)
    {
        $ids = $accounts->pluck('id')->toArray();
        /** @var Collection $query */
        $query = Auth::user()->transactionJournals()
                     ->transactionTypes([TransactionType::WITHDRAWAL])
                     ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                     ->leftJoin('budget_transaction_journal', 'transaction_journals.id', '=', 'budget_transaction_journal.transaction_journal_id')
                     ->whereIn('transactions.account_id', $ids)
                     ->where('transactions.amount', '<', 0)
                     ->before($end)
                     ->after($start)
                     ->groupBy('budget_id')
                     ->groupBy('dateFormatted')
                     ->get(
                         ['transaction_journals.date as dateFormatted', 'budget_transaction_journal.budget_id',
                          DB::Raw('SUM(`transactions`.`amount`) AS `sum`')]
                     );

        $return = [];
        foreach ($query->toArray() as $entry) {
            $budgetId = $entry['budget_id'];
            if (!isset($return[$budgetId])) {
                $return[$budgetId] = [];
            }
            $return[$budgetId][$entry['dateFormatted']] = $entry['sum'];
        }

        return $return;
    }

    /**
     * Returns a list of expenses (in the field "spent", grouped per budget per account.
     *
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function spentPerBudgetPerAccount(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end)
    {
        $accountIds = $accounts->pluck('id')->toArray();
        $budgetIds  = $budgets->pluck('id')->toArray();
        $set        = Auth::user()->transactionjournals()
                          ->leftJoin(
                              'transactions AS t_from', function (JoinClause $join) {
                              $join->on('transaction_journals.id', '=', 't_from.transaction_journal_id')->where('t_from.amount', '<', 0);
                          }
                          )
                          ->leftJoin(
                              'transactions AS t_to', function (JoinClause $join) {
                              $join->on('transaction_journals.id', '=', 't_to.transaction_journal_id')->where('t_to.amount', '>', 0);
                          }
                          )
                          ->leftJoin('budget_transaction_journal', 'transaction_journals.id', '=', 'budget_transaction_journal.transaction_journal_id')
                          ->whereIn('t_from.account_id', $accountIds)
                          ->whereNotIn('t_to.account_id', $accountIds)
                          ->where(
                              function (Builder $q) use ($budgetIds) {
                                  $q->whereIn('budget_transaction_journal.budget_id', $budgetIds);
                                  $q->orWhereNull('budget_transaction_journal.budget_id');
                              }
                          )
                          ->after($start)
                          ->before($end)
                          ->groupBy('t_from.account_id')
                          ->groupBy('budget_transaction_journal.budget_id')
                          ->transactionTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER, TransactionType::OPENING_BALANCE])
                          ->get(
                              [
                                  't_from.account_id', 'budget_transaction_journal.budget_id',
                                  DB::Raw('SUM(`t_from`.`amount`) AS `spent`')
                              ]
                          );

        return $set;

    }
}
