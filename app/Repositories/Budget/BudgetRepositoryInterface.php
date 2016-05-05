<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\Budget;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\LimitRepetition;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface BudgetRepositoryInterface
 *
 * @package FireflyIII\Repositories\Budget
 */
interface BudgetRepositoryInterface
{

    /**
     *
     * Same as ::spentInPeriod but corrects journals for a set of accounts
     *
     * @param Budget     $budget
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return string
     */
    public function balanceInPeriod(Budget $budget, Carbon $start, Carbon $end, Collection $accounts);

    /**
     * @return bool
     */
    public function cleanupBudgets(): bool;

    /**
     * @param Budget $budget
     *
     * @return bool
     */
    public function destroy(Budget $budget): bool;

    /**
     * @param Budget  $budget
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return Collection
     */
    public function expensesSplit(Budget $budget, Account $account, Carbon $start, Carbon $end): Collection;

    /**
     * Find a budget.
     *
     * @param int $budgetId
     *
     * @return Budget
     */
    public function find(int $budgetId): Budget;

    /**
     * @param Budget $budget
     *
     * @return Carbon
     */
    public function firstActivity(Budget $budget): Carbon;

    /**
     * @return Collection
     */
    public function getActiveBudgets(): Collection;

    /**
     * @param Carbon $start
     * @param Carbon $end
     * @param Budget $budget
     *
     * @return Collection
     */
    public function getAllBudgetLimitRepetitions(Carbon $start, Carbon $end, Budget $budget = null): Collection;

    /**
     * @param Account    $account
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function getAllWithoutBudget(Account $account, Collection $accounts, Carbon $start, Carbon $end): Collection;

    /**
     * Get the budgeted amounts for each budgets in each year.
     *
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function getBudgetedPerYear(Collection $budgets, Carbon $start, Carbon $end): Collection;

    /**
     * @return Collection
     */
    public function getBudgets(): Collection;

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
    public function getBudgetsAndExpensesPerMonth(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * Returns an array with every budget in it and the expenses for each budget
     * per year for.
     *
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @deprecated 
     *
     * @return array
     */
    public function getBudgetsAndExpensesPerYear(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * Returns a list of budgets, budget limits and limit repetitions
     * (doubling any of them in a left join)
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getBudgetsAndLimitsInRange(Carbon $start, Carbon $end): Collection;

    /**
     * Returns a list of budget limits that are valid in the current given range.
     *
     * @param Budget          $budget
     * @param Carbon          $start
     * @param Carbon          $end
     * @param LimitRepetition $ignore
     *
     * @return Collection
     */
    public function getValidRepetitions(Budget $budget, Carbon $start, Carbon $end, LimitRepetition $ignore) : Collection;

    /**
     * @param Budget $budget
     * @param string $repeatFreq
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return LimitRepetition
     */
    public function getCurrentRepetition(Budget $budget, string $repeatFreq, Carbon $start, Carbon $end): LimitRepetition;

    /**
     * Returns all expenses for the given budget and the given accounts, in the given period.
     *
     * @param Budget     $budget
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function getExpenses(Budget $budget, Collection $accounts, Carbon $start, Carbon $end):Collection;

    /**
     * @param Budget $budget
     *
     * @return Carbon
     */
    public function getFirstBudgetLimitDate(Budget $budget):Carbon;

    /**
     * @return Collection
     */
    public function getInactiveBudgets(): Collection;

    /**
     * Returns all the transaction journals for a limit, possibly limited by a limit repetition.
     *
     * @param Budget          $budget
     * @param LimitRepetition $repetition
     * @param int             $take
     *
     * @return LengthAwarePaginator
     */
    public function getJournals(Budget $budget, LimitRepetition $repetition = null, int $take = 50): LengthAwarePaginator;

    /**
     * @param Carbon $start
     * @param Carbon $end
     * @param int    $page
     * @param int    $pageSize
     *
     * @return LengthAwarePaginator
     */
    public function getWithoutBudget(Carbon $start, Carbon $end, int $page, int $pageSize = 50): LengthAwarePaginator;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function getWithoutBudgetForAccounts(Collection $accounts, Carbon $start, Carbon $end): Collection;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function getWithoutBudgetSum(Collection $accounts, Carbon $start, Carbon $end): string;

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
    public function spentAllPerDayForAccounts(Collection $accounts, Carbon $start, Carbon $end): array;

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
    public function spentPerBudgetPerAccount(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): Collection;

    /**
     * Returns an array with the following key:value pairs:
     *
     * yyyy-mm-dd:<amount>
     *
     * Where yyyy-mm-dd is the date and <amount> is the money spent using WITHDRAWALS in the $budget
     * from all the users accounts.
     *
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     * @param Collection $accounts
     *
     * @return array
     */
    public function spentPerDay(Budget $budget, Carbon $start, Carbon $end, Collection $accounts): array;

    /**
     * @param array $data
     *
     * @return Budget
     */
    public function store(array $data): Budget;

    /**
     * @param Budget $budget
     * @param array  $data
     *
     * @return Budget
     */
    public function update(Budget $budget, array $data) : Budget;

    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     * @param string $range
     * @param int    $amount
     *
     * @return BudgetLimit
     */
    public function updateLimitAmount(Budget $budget, Carbon $start, Carbon $end, string $range, int $amount) : BudgetLimit;

}
