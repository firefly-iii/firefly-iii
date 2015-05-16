<?php

namespace FireflyIII\Helpers\Report;

use App;
use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Account as AccountCollection;
use FireflyIII\Helpers\Collection\Budget as BudgetCollection;
use FireflyIII\Helpers\Collection\BudgetLine;
use FireflyIII\Helpers\Collection\Category as CategoryCollection;
use FireflyIII\Helpers\Collection\Expense;
use FireflyIII\Helpers\Collection\Income;
use FireflyIII\Models\Account;
use FireflyIII\Models\LimitRepetition;

/**
 * Class ReportHelper
 *
 * @package FireflyIII\Helpers\Report
 */
class ReportHelper implements ReportHelperInterface
{

    /** @var ReportQueryInterface */
    protected $query;

    /**
     * @param ReportHelperInterface $helper
     */
    public function __construct(ReportQueryInterface $query)
    {
        $this->query = $query;

    }


    /**
     * This method generates a full report for the given period on all
     * the users asset and cash accounts.
     *
     * @param Carbon $date
     * @param Carbon $end
     * @param        $shared
     *
     * @return Account
     */
    public function getAccountReport(Carbon $date, Carbon $end, $shared)
    {


        $accounts = $this->query->getAllAccounts($date, $end, $shared);
        $start    = 0;
        $end      = 0;
        $diff     = 0;

        // summarize:
        foreach ($accounts as $account) {
            $start += $account->startBalance;
            $end += $account->endBalance;
            $diff += ($account->endBalance - $account->startBalance);
        }

        $object = new AccountCollection;
        $object->setStart($start);
        $object->setEnd($end);
        $object->setDifference($diff);
        $object->setAccounts($accounts);

        return $object;
    }

    /**
     * @param Carbon  $start
     * @param Carbon  $end
     * @param boolean $shared
     *
     * @return BudgetCollection
     */
    public function getBudgetReport(Carbon $start, Carbon $end, $shared)
    {
        $object = new BudgetCollection;
        /** @var \FireflyIII\Repositories\Budget\BudgetRepositoryInterface $repository */
        $repository = App::make('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $set        = $repository->getBudgets();

        foreach ($set as $budget) {

            $repetitions = $repository->getBudgetLimitRepetitions($budget, $start, $end);

            // no repetition(s) for this budget:
            if ($repetitions->count() == 0) {
                $spent      = $repository->spentInPeriod($budget, $start, $end, $shared);
                $budgetLine = new BudgetLine;
                $budgetLine->setBudget($budget);
                $budgetLine->setOverspent($spent);
                $object->addOverspent($spent);
                $object->addBudgetLine($budgetLine);
                continue;
            }

            // one or more repetitions for budget:
            /** @var LimitRepetition $repetition */
            foreach ($repetitions as $repetition) {
                $budgetLine = new BudgetLine;
                $budgetLine->setBudget($budget);
                $budgetLine->setRepetition($repetition);
                $expenses  = $repository->spentInPeriod($budget, $repetition->startdate, $repetition->enddate, $shared);
                $left      = $expenses < floatval($repetition->amount) ? floatval($repetition->amount) - $expenses : 0;
                $spent     = $expenses > floatval($repetition->amount) ? 0 : $expenses;
                $overspent = $expenses > floatval($repetition->amount) ? $expenses - floatval($repetition->amount) : 0;

                $budgetLine->setLeft($left);
                $budgetLine->setSpent($spent);
                $budgetLine->setOverspent($overspent);
                $budgetLine->setBudgeted($repetition->amount);

                $object->addBudgeted($repetition->amount);
                $object->addSpent($spent);
                $object->addLeft($left);
                $object->addOverspent($overspent);
                $object->addBudgetLine($budgetLine);

            }

        }

        // stuff outside of budgets:
        $noBudget   = $repository->getWithoutBudgetSum($start, $end);
        $budgetLine = new BudgetLine;
        $budgetLine->setOverspent($noBudget);
        $object->addOverspent($noBudget);
        $object->addBudgetLine($budgetLine);

        return $object;
    }

    /**
     * @param Carbon  $start
     * @param Carbon  $end
     * @param boolean $shared
     *
     * @return CategoryCollection
     */
    public function getCategoryReport(Carbon $start, Carbon $end, $shared)
    {
        return null;
    }

    /**
     * Get a full report on the users expenses during the period.
     *
     * @param Carbon  $start
     * @param Carbon  $end
     * @param boolean $shared
     *
     * @return Expense
     */
    public function getExpenseReport($start, $end, $shared)
    {
        $object = new Expense;
        $set    = $this->query->expenseInPeriod($start, $end, $shared);
        foreach ($set as $entry) {
            $object->addToTotal($entry->queryAmount);
            $object->addOrCreateExpense($entry);
        }

        return $object;
    }

    /**
     * Get a full report on the users incomes during the period.
     *
     * @param Carbon  $start
     * @param Carbon  $end
     * @param boolean $shared
     *
     * @return Income
     */
    public function getIncomeReport($start, $end, $shared)
    {
        $object = new Income;
        $set    = $this->query->incomeInPeriod($start, $end, $shared);
        foreach ($set as $entry) {
            $object->addToTotal($entry->queryAmount);
            $object->addOrCreateIncome($entry);
        }

        return $object;
    }

    /**
     * @param Carbon $date
     *
     * @return array
     */
    public function listOfMonths(Carbon $date)
    {

        $start  = clone $date;
        $end    = Carbon::now();
        $months = [];
        while ($start <= $end) {
            $year            = $start->year;
            $months[$year][] = [
                'formatted' => $start->formatLocalized('%B %Y'),
                'month'     => $start->month,
                'year'      => $year,
            ];
            $start->addMonth();
        }

        return $months;
    }
}
