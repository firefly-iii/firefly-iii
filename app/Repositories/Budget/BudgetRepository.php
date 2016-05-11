<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\Budget;

use Carbon\Carbon;
use FireflyIII\Events\BudgetLimitStored;
use FireflyIII\Events\BudgetLimitUpdated;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Log;

/**
 * Class BudgetRepository
 *
 * @package FireflyIII\Repositories\Budget
 */
class BudgetRepository implements BudgetRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * BudgetRepository constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    //    /**
    //     * @param Budget     $budget
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     * @param Collection $accounts
    //     *
    //     * @return string
    //     */
    //    public function balanceInPeriod(Budget $budget, Carbon $start, Carbon $end, Collection $accounts): string
    //    {
    //        return $this->commonBalanceInPeriod($budget, $start, $end, $accounts);
    //    }

    //    /**
    //     * @return bool
    //     */
    //    public function cleanupBudgets(): bool
    //    {
    //        // delete limits with amount 0:
    //        BudgetLimit::where('amount', 0)->delete();
    //
    //        return true;
    //
    //    }

    /**
     * @param Budget $budget
     *
     * @return bool
     */
    public function destroy(Budget $budget): bool
    {
        $budget->delete();

        return true;
    }

    //    /**
    //     * @param Budget  $budget
    //     * @param Account $account
    //     * @param Carbon  $start
    //     * @param Carbon  $end
    //     *
    //     * @return Collection
    //     */
    //    public function expensesSplit(Budget $budget, Account $account, Carbon $start, Carbon $end): Collection
    //    {
    //        return $budget->transactionjournals()->expanded()
    //                      ->before($end)
    //                      ->after($start)
    //                      ->where('source_account.id', $account->id)
    //                      ->get(TransactionJournal::queryFields());
    //    }

    /**
     * Find a budget.
     *
     * @param int $budgetId
     *
     * @return Budget
     */
    public function find(int $budgetId): Budget
    {
        $budget = $this->user->budgets()->find($budgetId);
        if (is_null($budget)) {
            $budget = new Budget;
        }

        return $budget;
    }

    //    /**
    //     * @param Budget $budget
    //     *
    //     * @return Carbon
    //     */
    //    public function firstActivity(Budget $budget): Carbon
    //    {
    //        $first = $budget->transactionjournals()->orderBy('date', 'ASC')->first();
    //        if ($first) {
    //            return $first->date;
    //        }
    //
    //        return new Carbon;
    //    }

    /**
     * This method returns the oldest journal or transaction date known to this budget.
     * Will cache result.
     *
     * @param Budget $budget
     *
     * @return Carbon
     */
    public function firstUseDate(Budget $budget): Carbon
    {
        $oldest  = Carbon::create()->startOfYear();
        $journal = $budget->transactionjournals()->orderBy('date', 'ASC')->first();
        if ($journal) {
            $oldest = $journal->date < $oldest ? $journal->date : $oldest;
        }

        $transaction = $budget
            ->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.id')
            ->orderBy('transaction_journals.date', 'ASC')->first(['transactions.*', 'transaction_journals.date']);
        if ($transaction) {
            $oldest = $transaction->date < $oldest ? $transaction->date : $oldest;
        }

        return $oldest;

    }

    /**
     * @return Collection
     */
    public function getActiveBudgets(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->budgets()->where('active', 1)->get();

        $set = $set->sortBy(
            function (Budget $budget) {
                return strtolower($budget->name);
            }
        );

        return $set;
    }

    //    /**
    //     * @param Account    $account
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     * @param Collection $accounts
    //     *
    //     * @return Collection
    //     */
    //    public function getAllWithoutBudget(Account $account, Collection $accounts, Carbon $start, Carbon $end): Collection
    //    {
    //        $ids = $accounts->pluck('id')->toArray();
    //
    //        return $this->user
    //            ->transactionjournals()
    //            ->expanded()
    //            ->where('source_account.id', $account->id)
    //            ->whereNotIn('destination_account.id', $ids)
    //            ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
    //            ->whereNull('budget_transaction_journal.id')
    //            ->before($end)
    //            ->after($start)
    //            ->get(TransactionJournal::queryFields());
    //    }

    //    /**
    //     * Get the budgeted amounts for each budgets in each year.
    //     *
    //     * @param Collection $budgets
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     *
    //     * @return Collection
    //     */
    //    public function getBudgetedPerYear(Collection $budgets, Carbon $start, Carbon $end): Collection
    //    {
    //        $budgetIds = $budgets->pluck('id')->toArray();
    //
    //        $set = $this->user->budgets()
    //                          ->leftJoin('budget_limits', 'budgets.id', '=', 'budget_limits.budget_id')
    //                          ->leftJoin('limit_repetitions', 'limit_repetitions.budget_limit_id', '=', 'budget_limits.id')
    //                          ->where('limit_repetitions.startdate', '>=', $start->format('Y-m-d'))
    //                          ->where('limit_repetitions.enddate', '<=', $end->format('Y-m-d'))
    //                          ->groupBy('budgets.id')
    //                          ->groupBy('dateFormatted')
    //                          ->whereIn('budgets.id', $budgetIds)
    //                          ->get(
    //                              [
    //                                  'budgets.*',
    //                                  DB::raw('DATE_FORMAT(`limit_repetitions`.`startdate`,"%Y") as `dateFormatted`'),
    //                                  DB::raw('SUM(`limit_repetitions`.`amount`) as `budgeted`'),
    //                              ]
    //                          );
    //
    //        return $set;
    //    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getAllBudgetLimitRepetitions(Carbon $start, Carbon $end): Collection
    {
        $query = LimitRepetition::
        leftJoin('budget_limits', 'limit_repetitions.budget_limit_id', '=', 'budget_limits.id')
                                ->leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                                ->where('limit_repetitions.startdate', '<=', $end->format('Y-m-d 00:00:00'))
                                ->where('limit_repetitions.startdate', '>=', $start->format('Y-m-d 00:00:00'))
                                ->where('budgets.user_id', $this->user->id);

        $set = $query->get(['limit_repetitions.*', 'budget_limits.budget_id']);

        return $set;
    }

    //    /**
    //     * Returns an array with every budget in it and the expenses for each budget
    //     * per month.
    //     *
    //     * @param Collection $accounts
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     *
    //     * @return array
    //     */
    //    public function getBudgetsAndExpensesPerMonth(Collection $accounts, Carbon $start, Carbon $end): array
    //    {
    //        $ids = $accounts->pluck('id')->toArray();
    //
    //        /** @var Collection $set */
    //        $set = $this->user->budgets()
    //                          ->leftJoin('budget_transaction_journal', 'budgets.id', '=', 'budget_transaction_journal.budget_id')
    //                          ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'budget_transaction_journal.transaction_journal_id')
    //                          ->leftJoin(
    //                              'transactions', function (JoinClause $join) {
    //                              $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('transactions.amount', '<', 0);
    //                          }
    //                          )
    //                          ->groupBy('budgets.id')
    //                          ->groupBy('dateFormatted')
    //                          ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
    //                          ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
    //                          ->whereIn('transactions.account_id', $ids)
    //                          ->get(
    //                              [
    //                                  'budgets.*',
    //                                  DB::raw('DATE_FORMAT(`transaction_journals`.`date`, "%Y-%m") AS `dateFormatted`'),
    //                                  DB::raw('SUM(`transactions`.`amount`) AS `sumAmount`'),
    //                              ]
    //                          );
    //
    //        $set = $set->sortBy(
    //            function (Budget $budget) {
    //                return strtolower($budget->name);
    //            }
    //        );
    //
    //        $return = [];
    //        foreach ($set as $budget) {
    //            $id = $budget->id;
    //            if (!isset($return[$id])) {
    //                $return[$id] = [
    //                    'budget'  => $budget,
    //                    'entries' => [],
    //                ];
    //            }
    //            // store each entry:
    //            $return[$id]['entries'][$budget->dateFormatted] = $budget->sumAmount;
    //        }
    //
    //        return $return;
    //    }

    //    /**
    //     * Returns an array with every budget in it and the expenses for each budget
    //     * per year for.
    //     *
    //     * @param Collection $budgets
    //     * @param Collection $accounts
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     *
    //     * @deprecated
    //     *
    //     * @return array
    //     */
    //    public function getBudgetsAndExpensesPerYear(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): array
    //    {
    //        // get budgets,
    //        $ids       = $accounts->pluck('id')->toArray();
    //        $budgetIds = $budgets->pluck('id')->toArray();
    //
    //        /** @var Collection $set */
    //        $set = $this->user->budgets()
    //                          ->join('budget_transaction_journal', 'budgets.id', '=', 'budget_transaction_journal.budget_id')
    //                          ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'budget_transaction_journal.transaction_journal_id')
    //                          ->leftJoin(
    //                              'transactions', function (JoinClause $join) {
    //                              $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('transactions.amount', '<', 0);
    //                          }
    //                          )
    //                          ->groupBy('budgets.id')
    //                          ->groupBy('dateFormatted')
    //                          ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
    //                          ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
    //                          ->whereIn('transactions.account_id', $ids)
    //                          ->whereIn('budgets.id', $budgetIds)
    //                          ->get(
    //                              [
    //                                  'budgets.*',
    //                                  DB::raw('DATE_FORMAT(`transaction_journals`.`date`, "%Y") AS `dateFormatted`'),
    //                                  DB::raw('SUM(`transactions`.`amount`) AS `sumAmount`'),
    //                              ]
    //                          );
    //
    //        // run it again, for transactions this time.
    //        /** @var Collection $secondSet */
    //        $secondSet = $this->user->budgets()
    //                                ->join('budget_transaction', 'budgets.id', '=', 'budget_transaction.budget_id')
    //                                ->leftJoin('transactions', 'transactions.id', '=', 'budget_transaction.transaction_id')
    //                                ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
    //                                ->where('transactions.amount', '<', 0)
    //                                ->groupBy('budgets.id')
    //                                ->groupBy('dateFormatted')
    //                                ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
    //                                ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
    //                                ->whereIn('transactions.account_id', $ids)
    //                                ->whereIn('budgets.id', $budgetIds)
    //                                ->get(
    //                                    [
    //                                        'budgets.*',
    //                                        DB::raw('DATE_FORMAT(`transaction_journals`.`date`, "%Y") AS `dateFormatted`'),
    //                                        DB::raw('SUM(`transactions`.`amount`) AS `sumAmount`'),
    //                                    ]
    //                                );
    //
    //        $set = $set->sortBy(
    //            function (Budget $budget) {
    //                return strtolower($budget->name);
    //            }
    //        );
    //
    //        $return = [];
    //        foreach ($set as $budget) {
    //            Log::debug('First set, budget #' . $budget->id . ' (' . $budget->name . ')');
    //            $id = $budget->id;
    //            if (!isset($return[$id])) {
    //                Log::debug('$return[$id] is not set, now created.');
    //                $return[$id] = [
    //                    'budget'  => $budget,
    //                    'entries' => [],
    //                ];
    //            }
    //            Log::debug('Add new entry to entries, for ' . $budget->dateFormatted . ' and amount ' . $budget->sumAmount);
    //            // store each entry:
    //            $return[$id]['entries'][$budget->dateFormatted] = $budget->sumAmount;
    //        }
    //        unset($budget);
    //
    //        // run the second set:
    //        foreach ($secondSet as $entry) {
    //            $id = $entry->id;
    //            // create it if it still does not exist (not really likely)
    //            if (!isset($return[$id])) {
    //                $return[$id] = [
    //                    'budget'  => $entry,
    //                    'entries' => [],
    //                ];
    //            }
    //            // this one might be filled too:
    //            $startAmount = $return[$id]['entries'][$entry->dateFormatted] ?? '0';
    //            // store each entry:
    //            $return[$id]['entries'][$entry->dateFormatted] = bcadd($startAmount, $entry->sumAmount);
    //        }
    //
    //        return $return;
    //    }

    //    /**
    //     * Returns a list of budgets, budget limits and limit repetitions
    //     * (doubling any of them in a left join)
    //     *
    //     * @param Carbon $start
    //     * @param Carbon $end
    //     *
    //     * @return Collection
    //     */
    //    public function getBudgetsAndLimitsInRange(Carbon $start, Carbon $end): Collection
    //    {
    //        /** @var Collection $set */
    //        $set = $this->user
    //            ->budgets()
    //            ->leftJoin('budget_limits', 'budget_limits.budget_id', '=', 'budgets.id')
    //            ->leftJoin('limit_repetitions', 'limit_repetitions.budget_limit_id', '=', 'budget_limits.id')
    //            ->where(
    //                function (Builder $query) use ($start, $end) {
    //                    $query->where(
    //                        function (Builder $query) use ($start, $end) {
    //                            $query->where('limit_repetitions.startdate', '>=', $start->format('Y-m-d'));
    //                            $query->where('limit_repetitions.startdate', '<=', $end->format('Y-m-d'));
    //                        }
    //                    );
    //                    $query->orWhere(
    //                        function (Builder $query) {
    //                            $query->whereNull('limit_repetitions.startdate');
    //                            $query->whereNull('limit_repetitions.enddate');
    //                        }
    //                    );
    //                }
    //            )
    //            ->orderBy('budgets.id', 'budget_limits.startdate', 'limit_repetitions.enddate')
    //            ->get(['budgets.*', 'limit_repetitions.startdate', 'limit_repetitions.enddate', 'limit_repetitions.amount']);
    //
    //        $set = $set->sortBy(
    //            function (Budget $budget) {
    //                return strtolower($budget->name);
    //            }
    //        );
    //
    //        return $set;
    //
    //    }

    //    /**
    //     * @param Budget $budget
    //     * @param string $repeatFreq
    //     * @param Carbon $start
    //     * @param Carbon $end
    //     *
    //     * @return LimitRepetition
    //     */
    //    public function getCurrentRepetition(Budget $budget, string $repeatFreq, Carbon $start, Carbon $end): LimitRepetition
    //    {
    //        $data = $budget->limitrepetitions()
    //                       ->where('budget_limits.repeat_freq', $repeatFreq)
    //                       ->where('limit_repetitions.startdate', $start->format('Y-m-d 00:00:00'))
    //                       ->where('limit_repetitions.enddate', $end->format('Y-m-d 00:00:00'))
    //                       ->first(['limit_repetitions.*']);
    //        if (is_null($data)) {
    //            return new LimitRepetition;
    //        }
    //
    //        return $data;
    //    }

    //    /**
    //     * Returns all expenses for the given budget and the given accounts, in the given period.
    //     *
    //     * @param Budget     $budget
    //     * @param Collection $accounts
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     *
    //     * @return Collection
    //     */
    //    public function getExpenses(Budget $budget, Collection $accounts, Carbon $start, Carbon $end):Collection
    //    {
    //        $ids = $accounts->pluck('id')->toArray();
    //        $set = $budget->transactionjournals()
    //                      ->before($end)
    //                      ->after($start)
    //                      ->expanded()
    //                      ->where('transaction_types.type', TransactionType::WITHDRAWAL)
    //                      ->whereIn('source_account.id', $ids)
    //                      ->get(TransactionJournal::queryFields());
    //
    //        return $set;
    //    }

    //    /**
    //     * @param Budget $budget
    //     *
    //     * @return Carbon
    //     */
    //    public function getFirstBudgetLimitDate(Budget $budget): Carbon
    //    {
    //        $limit = $budget->budgetlimits()->orderBy('startdate', 'ASC')->first();
    //        if ($limit) {
    //            return $limit->startdate;
    //        }
    //
    //        return Carbon::now()->startOfYear();
    //    }

    /**
     * @return Collection
     */
    public function getBudgets(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->budgets()->get();

        $set = $set->sortBy(
            function (Budget $budget) {
                return strtolower($budget->name);
            }
        );

        return $set;
    }

    //    /**
    //     * Returns all the transaction journals for a limit, possibly limited by a limit repetition.
    //     *
    //     * @param Budget          $budget
    //     * @param LimitRepetition $repetition
    //     * @param int             $take
    //     *
    //     * @return LengthAwarePaginator
    //     */
    //    public function getJournals(Budget $budget, LimitRepetition $repetition = null, int $take = 50): LengthAwarePaginator
    //    {
    //        $offset     = intval(Input::get('page')) > 0 ? intval(Input::get('page')) * $take : 0;
    //        $setQuery   = $budget->transactionjournals()->expanded()
    //                             ->take($take)->offset($offset)
    //                             ->orderBy('transaction_journals.date', 'DESC')
    //                             ->orderBy('transaction_journals.order', 'ASC')
    //                             ->orderBy('transaction_journals.id', 'DESC');
    //        $countQuery = $budget->transactionjournals();
    //
    //
    //        if (!is_null($repetition->id)) {
    //            $setQuery->after($repetition->startdate)->before($repetition->enddate);
    //            $countQuery->after($repetition->startdate)->before($repetition->enddate);
    //        }
    //
    //
    //        $set   = $setQuery->get(TransactionJournal::queryFields());
    //        $count = $countQuery->count();
    //
    //
    //        $paginator = new LengthAwarePaginator($set, $count, $take, $offset);
    //
    //        return $paginator;
    //    }

    //    /**
    //     * Returns a list of budget limits that are valid in the current given range.
    //     * $ignore is optional. Send an empty limit rep.
    //     *
    //     * @param Budget          $budget
    //     * @param Carbon          $start
    //     * @param Carbon          $end
    //     * @param LimitRepetition $ignore
    //     *
    //     * @return Collection
    //     */
    //    public function getValidRepetitions(Budget $budget, Carbon $start, Carbon $end, LimitRepetition $ignore) : Collection
    //    {
    //        $query = $budget->limitrepetitions()
    //            // starts before start time, and the end also after start time.
    //                        ->where('limit_repetitions.enddate', '>=', $start->format('Y-m-d 00:00:00'))
    //                        ->where('limit_repetitions.startdate', '<=', $end->format('Y-m-d 00:00:00'));
    //        if (!is_null($ignore->id)) {
    //            $query->where('limit_repetitions.id', '!=', $ignore->id);
    //        }
    //        $data = $query->get(['limit_repetitions.*']);
    //
    //        return $data;
    //    }

    //    /**
    //     * @param Carbon $start
    //     * @param Carbon $end
    //     * @param int    $page
    //     * @param int    $pageSize
    //     *
    //     * @return LengthAwarePaginator
    //     */
    //    public function getWithoutBudget(Carbon $start, Carbon $end, int $page, int $pageSize = 50): LengthAwarePaginator
    //    {
    //        $offset = ($page - 1) * $pageSize;
    //        $query  = $this->user
    //            ->transactionjournals()
    //            ->expanded()
    //            ->where('transaction_types.type', TransactionType::WITHDRAWAL)
    //            ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
    //            ->whereNull('budget_transaction_journal.id')
    //            ->before($end)
    //            ->after($start);
    //
    //        $count     = $query->count();
    //        $set       = $query->take($pageSize)->offset($offset)->get(TransactionJournal::queryFields());
    //        $paginator = new LengthAwarePaginator($set, $count, $pageSize, $page);
    //
    //        return $paginator;
    //    }

    //    /**
    //     * @param Collection $accounts
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     *
    //     * @return Collection
    //     */
    //    public function getWithoutBudgetForAccounts(Collection $accounts, Carbon $start, Carbon $end): Collection
    //    {
    //        $ids = $accounts->pluck('id')->toArray();
    //
    //        return $this->user
    //            ->transactionjournals()
    //            ->expanded()
    //            ->whereIn('source_account.id', $ids)
    //            ->where('transaction_types.type', TransactionType::WITHDRAWAL)
    //            ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
    //            ->whereNull('budget_transaction_journal.id')
    //            ->before($end)
    //            ->after($start)
    //            ->get(TransactionJournal::queryFields());
    //    }

    //    /**
    //     * @param Collection $accounts
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     *
    //     * @return string
    //     */
    //    public function getWithoutBudgetSum(Collection $accounts, Carbon $start, Carbon $end): string
    //    {
    //        $ids   = $accounts->pluck('id')->toArray();
    //        $entry = $this->user
    //            ->transactionjournals()
    //            ->whereNotIn(
    //                'transaction_journals.id', function (QueryBuilder $query) use ($start, $end) {
    //                $query
    //                    ->select('transaction_journals.id')
    //                    ->from('transaction_journals')
    //                    ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
    //                    ->where('transaction_journals.date', '>=', $start->format('Y-m-d 00:00:00'))
    //                    ->where('transaction_journals.date', '<=', $end->format('Y-m-d 00:00:00'))
    //                    ->whereNotNull('budget_transaction_journal.budget_id');
    //            }
    //            )
    //            ->after($start)
    //            ->before($end)
    //            ->leftJoin(
    //                'transactions', function (JoinClause $join) {
    //                $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('transactions.amount', '<', 0);
    //            }
    //            )
    //            ->whereIn('transactions.account_id', $ids)
    //            //->having('transaction_count', '=', 1) TO DO check if this still works
    //            ->transactionTypes([TransactionType::WITHDRAWAL])
    //            ->first(
    //                [
    //                    DB::raw('SUM(`transactions`.`amount`) as `journalAmount`'),
    //                    DB::raw('COUNT(`transactions`.`id`) as `transaction_count`'),
    //                ]
    //            );
    //        if (is_null($entry)) {
    //            return '0';
    //        }
    //        if (is_null($entry->journalAmount)) {
    //            return '0';
    //        }
    //
    //        return $entry->journalAmount;
    //    }

    //    /**
    //     * Returns an array with the following key:value pairs:
    //     *
    //     * yyyy-mm-dd:<array>
    //     *
    //     * That array contains:
    //     *
    //     * budgetid:<amount>
    //     *
    //     * Where yyyy-mm-dd is the date and <amount> is the money spent using WITHDRAWALS in the $budget
    //     * from the given users accounts..
    //     *
    //     * @param Collection $accounts
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     *
    //     * @return array
    //     */
    //    public function spentAllPerDayForAccounts(Collection $accounts, Carbon $start, Carbon $end): array
    //    {
    //        $ids = $accounts->pluck('id')->toArray();
    //        /** @var Collection $query */
    //        $query = $this->user->transactionJournals()
    //                            ->transactionTypes([TransactionType::WITHDRAWAL])
    //                            ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
    //                            ->leftJoin('budget_transaction_journal', 'transaction_journals.id', '=', 'budget_transaction_journal.transaction_journal_id')
    //                            ->whereIn('transactions.account_id', $ids)
    //                            ->where('transactions.amount', '<', 0)
    //                            ->before($end)
    //                            ->after($start)
    //                            ->groupBy('budget_id')
    //                            ->groupBy('dateFormatted')
    //                            ->get(
    //                                ['transaction_journals.date as dateFormatted', 'budget_transaction_journal.budget_id',
    //                                 DB::raw('SUM(`transactions`.`amount`) AS `sum`')]
    //                            );
    //
    //        $return = [];
    //        foreach ($query->toArray() as $entry) {
    //            $budgetId = $entry['budget_id'];
    //            if (!isset($return[$budgetId])) {
    //                $return[$budgetId] = [];
    //            }
    //            $return[$budgetId][$entry['dateFormatted']] = $entry['sum'];
    //        }
    //
    //        return $return;
    //    }

    //    /**
    //     * Returns a list of expenses (in the field "spent", grouped per budget per account.
    //     *
    //     * @param Collection $budgets
    //     * @param Collection $accounts
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     *
    //     * @return Collection
    //     */
    //    public function spentPerBudgetPerAccount(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): Collection
    //    {
    //        $accountIds = $accounts->pluck('id')->toArray();
    //        $budgetIds  = $budgets->pluck('id')->toArray();
    //        $set        = $this->user->transactionjournals()
    //                                 ->leftJoin(
    //                                     'transactions AS t_from', function (JoinClause $join) {
    //                                     $join->on('transaction_journals.id', '=', 't_from.transaction_journal_id')->where('t_from.amount', '<', 0);
    //                                 }
    //                                 )
    //                                 ->leftJoin(
    //                                     'transactions AS t_to', function (JoinClause $join) {
    //                                     $join->on('transaction_journals.id', '=', 't_to.transaction_journal_id')->where('t_to.amount', '>', 0);
    //                                 }
    //                                 )
    //                                 ->leftJoin('budget_transaction_journal', 'transaction_journals.id', '=', 'budget_transaction_journal.transaction_journal_id')
    //                                 ->whereIn('t_from.account_id', $accountIds)
    //                                 ->whereNotIn('t_to.account_id', $accountIds)
    //                                 ->where(
    //                                     function (Builder $q) use ($budgetIds) {
    //                                         $q->whereIn('budget_transaction_journal.budget_id', $budgetIds);
    //                                         $q->orWhereNull('budget_transaction_journal.budget_id');
    //                                     }
    //                                 )
    //                                 ->after($start)
    //                                 ->before($end)
    //                                 ->groupBy('t_from.account_id')
    //                                 ->groupBy('budget_transaction_journal.budget_id')
    //                                 ->transactionTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])// opening balance is not an expense.
    //                                 ->get(
    //                [
    //                    't_from.account_id', 'budget_transaction_journal.budget_id',
    //                    DB::raw('SUM(`t_from`.`amount`) AS `spent`'),
    //                ]
    //            );
    //
    //        return $set;
    //
    //    }

    //    /**
    //     * Returns an array with the following key:value pairs:
    //     *
    //     * yyyy-mm-dd:<amount>
    //     *
    //     * Where yyyy-mm-dd is the date and <amount> is the money spent using DEPOSITS in the $budget
    //     * from all the users accounts.
    //     *
    //     * @param Budget     $budget
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     * @param Collection $accounts
    //     *
    //     * @return array
    //     */
    //    public function spentPerDay(Budget $budget, Carbon $start, Carbon $end, Collection $accounts): array
    //    {
    //        /** @var Collection $query */
    //        $query = $budget->transactionjournals()
    //                        ->transactionTypes([TransactionType::WITHDRAWAL])
    //                        ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
    //                        ->where('transactions.amount', '<', 0)
    //                        ->before($end)
    //                        ->after($start)
    //                        ->groupBy('dateFormatted')->get(['transaction_journals.date as dateFormatted', DB::raw('SUM(`transactions`.`amount`) AS `sum`')]);
    //
    //        $return = [];
    //        foreach ($query->toArray() as $entry) {
    //            $return[$entry['dateFormatted']] = $entry['sum'];
    //        }
    //
    //        // also search transactions:
    //        $query = $budget->transactions()
    //                        ->transactionTypes([TransactionType::WITHDRAWAL])
    //                        ->where('transactions.amount', '<', 0)
    //                        ->before($end)
    //                        ->after($start)
    //                        ->groupBy('dateFormatted')->get(['transaction_journals.date as dateFormatted', DB::raw('SUM(`transactions`.`amount`) AS `sum`')]);
    //        foreach ($query as $newEntry) {
    //            // add to return array.
    //            $date = $newEntry['dateFormatted'];
    //            if (isset($return[$date])) {
    //                $return[$date] = bcadd($newEntry['sum'], $return[$date]);
    //                continue;
    //            }
    //
    //            $return[$date] = $newEntry['sum'];
    //        }
    //
    //        return $return;
    //    }

    /**
     * @return Collection
     */
    public function getInactiveBudgets(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->budgets()->where('active', 0)->get();

        $set = $set->sortBy(
            function (Budget $budget) {
                return strtolower($budget->name);
            }
        );

        return $set;
    }

    /**
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function journalsInPeriod(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): Collection
    {
        $return     = new Collection;
        $accountIds = [];
        // expand the number of grabbed fields:
        $fields   = TransactionJournal::queryFields();
        $fields[] = 'source.account_id';
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
        }

        // first get all journals for all budget(s):
        $journalQuery = $this->user->transactionjournals()
                                   ->expanded()
                                   ->before($end)
                                   ->sortCorrectly()
                                   ->after($start)
                                   ->leftJoin(
                                       'transactions as source',
                                       function (JoinClause $join) {
                                           $join->on('source.transaction_journal_id', '=', 'transaction_journals.id')->where('source.amount', '<', '0');
                                       }
                                   )
                                   ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                                   ->whereIn('budget_transaction_journal.budget_id', $budgets->pluck('id')->toArray());
        // add account id's, if relevant:
        if (count($accountIds) > 0) {
            $journalQuery->whereIn('source.account_id', $accountIds);
        }
        // get them:
        $journals = $journalQuery->get(TransactionJournal::queryFields());
        //Log::debug('journalsInPeriod journal count is ' . $journals->count());

        // then get transactions themselves.
        $transactionQuery = $this->user->transactionjournals()
                                       ->expanded()
                                       ->before($end)
                                       ->sortCorrectly()
                                       ->after($start)
                                       ->leftJoin('transactions as related', 'related.transaction_journal_id', '=', 'transaction_journals.id')
                                       ->leftJoin('budget_transaction', 'budget_transaction.transaction_id', '=', 'related.id')
                                       ->leftJoin(
                                           'transactions as source',
                                           function (JoinClause $join) {
                                               $join->on('source.transaction_journal_id', '=', 'transaction_journals.id')->where('source.amount', '<', '0');
                                           }
                                       )
                                       ->whereIn('budget_transaction.budget_id', $budgets->pluck('id')->toArray());

        if (count($accountIds) > 0) {
            $transactionQuery->whereIn('source.account_id', $accountIds);
        }

        $transactions = $transactionQuery->get($fields);

        // return complete set:
        $return = $return->merge($transactions);
        $return = $return->merge($journals);

        return $return;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function journalsInPeriodWithoutBudget(Collection $accounts, Carbon $start, Carbon $end): Collection
    {
        $accountIds = [];
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
        }

        /** @var Collection $set */
        $query = $this->user
            ->transactionjournals()
            ->expanded()
            ->sortCorrectly()
            ->transactionTypes([TransactionType::WITHDRAWAL])
            ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
            ->whereNull('budget_transaction_journal.id')
            ->leftJoin(
                'transactions as source',
                function (JoinClause $join) {
                    $join->on('source.transaction_journal_id', '=', 'transaction_journals.id')->where('source.amount', '<', '0');
                }
            )
            ->before($end)
            ->after($start)->with(
                [
                    'transactions' => function (HasMany $query) {
                        $query->where('transactions.amount', '<', 0);
                    },
                    'transactions.budgets',
                ]
            );

        // add account id's, if relevant:
        if (count($accountIds) > 0) {
            $query->whereIn('source.account_id', $accountIds);
        }

        $set = $query->get(TransactionJournal::queryFields());
        $set = $set->filter(
            function (TransactionJournal $journal) {
                foreach ($journal->transactions as $t) {
                    if ($t->budgets->count() === 0) {
                        return $journal;
                    }
                }
            }
        );

        return $set;
    }

    /**
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriod(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end) : string
    {
        $set = $this->journalsInPeriod($budgets, $accounts, $start, $end);
        //Log::debug('spentInPeriod set count is ' . $set->count());
        $sum = '0';
        /** @var TransactionJournal $journal */
        foreach ($set as $journal) {
            $sum = bcadd($sum, TransactionJournal::amount($journal));
        }

        Log::debug('spentInPeriod between ' . $start->format('Y-m-d') . ' and ' . $end->format('Y-m-d') . ' is ' . $sum);

        return $sum;
    }

    /**
     * @param array $data
     *
     * @return Budget
     */
    public function store(array $data): Budget
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
    public function update(Budget $budget, array $data): Budget
    {
        // update the account:
        $budget->name   = $data['name'];
        $budget->active = $data['active'];
        $budget->save();

        return $budget;
    }

    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     * @param string $range
     * @param int    $amount
     *
     * @return BudgetLimit
     */
    public function updateLimitAmount(Budget $budget, Carbon $start, Carbon $end, string $range, int $amount) : BudgetLimit
    {
        // there might be a budget limit for this startdate:
        $repeatFreq = config('firefly.range_to_repeat_freq.' . $range);
        /** @var BudgetLimit $limit */
        $limit = $budget->budgetlimits()
                        ->where('budget_limits.startdate', $start)
                        ->where('budget_limits.repeat_freq', $repeatFreq)->first(['budget_limits.*']);

        // delete if amount is zero.
        if (!is_null($limit) && $amount <= 0.0) {
            $limit->delete();

            return new BudgetLimit;
        }
        // update if exists:
        if (!is_null($limit)) {
            $limit->amount = $amount;
            $limit->save();

            // fire event to create or update LimitRepetition.
            event(new BudgetLimitUpdated($limit, $end));

            return $limit;
        }

        // create one and return it.
        $limit = new BudgetLimit;
        $limit->budget()->associate($budget);
        $limit->startdate   = $start;
        $limit->amount      = $amount;
        $limit->repeat_freq = $repeatFreq;
        $limit->repeats     = 0;
        $limit->save();
        event(new BudgetLimitStored($limit, $end));


        // likewise, there should be a limit repetition to match the end date
        // (which is always the end of the month) but that is caught by an event.
        // so handled automatically.

        return $limit;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriodWithoutBudget(Collection $accounts, Carbon $start, Carbon $end): string
    {
        $set = $this->journalsInPeriodWithoutBudget($accounts, $start, $end);
        //Log::debug('spentInPeriod set count is ' . $set->count());
        $sum = '0';
        /** @var TransactionJournal $journal */
        foreach ($set as $journal) {
            $sum = bcadd($sum, TransactionJournal::amount($journal));
        }

        Log::debug('spentInPeriodWithoutBudget between ' . $start->format('Y-m-d') . ' and ' . $end->format('Y-m-d') . ' is ' . $sum);

        return $sum;
    }
}
