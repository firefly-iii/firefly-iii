<?php

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Account as AccountCollection;
use FireflyIII\Helpers\Collection\Balance;
use FireflyIII\Helpers\Collection\BalanceEntry;
use FireflyIII\Helpers\Collection\BalanceHeader;
use FireflyIII\Helpers\Collection\BalanceLine;
use FireflyIII\Helpers\Collection\Bill as BillCollection;
use FireflyIII\Helpers\Collection\BillLine;
use FireflyIII\Helpers\Collection\Budget as BudgetCollection;
use FireflyIII\Helpers\Collection\BudgetLine;
use FireflyIII\Helpers\Collection\Category as CategoryCollection;
use FireflyIII\Helpers\Collection\Expense;
use FireflyIII\Helpers\Collection\Income;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget as BudgetModel;
use FireflyIII\Models\LimitRepetition;
use Illuminate\Support\Collection;
use Steam;

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
     * @codeCoverageIgnore
     *
     * @param ReportQueryInterface $query
     *
     */
    public function __construct(ReportQueryInterface $query)
    {
        $this->query = $query;

    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return CategoryCollection
     */
    public function getCategoryReport(Carbon $start, Carbon $end, Collection $accounts)
    {
        $object = new CategoryCollection;

        /**
         * GET CATEGORIES:
         */
        /** @var \FireflyIII\Repositories\Category\CategoryRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Category\CategoryRepositoryInterface');

        /** @var \FireflyIII\Repositories\Category\SingleCategoryRepositoryInterface $singleRepository */
        $singleRepository = app('FireflyIII\Repositories\Category\SingleCategoryRepositoryInterface');

        $set        = $repository->getCategories();
        foreach ($set as $category) {
            $spent           = $singleRepository->balanceInPeriod($category, $start, $end, $accounts);
            $category->spent = $spent;
            $object->addCategory($category);
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
            $year = $start->year;

            if (!isset($months[$year])) {
                $months[$year] = [
                    'start'  => Carbon::createFromDate($year, 1, 1)->format('Y-m-d'),
                    'end'    => Carbon::createFromDate($year, 12, 31)->format('Y-m-d'),
                    'months' => [],
                ];
            }

            $currentEnd = clone $start;
            $currentEnd->endOfMonth();
            $months[$year]['months'][] = [
                'formatted' => $start->formatLocalized('%B %Y'),
                'start'     => $start->format('Y-m-d'),
                'end'       => $currentEnd->format('Y-m-d'),
                'month'     => $start->month,
                'year'      => $year,
            ];
            $start->addMonth();
        }

        return $months;
    }

    /**
     * This method generates a full report for the given period on all
     * given accounts
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return AccountCollection
     */
    public function getAccountReport(Carbon $start, Carbon $end, Collection $accounts)
    {
        $startAmount = '0';
        $endAmount   = '0';
        $diff        = '0';
        bcscale(2);

        $accounts->each(
            function (Account $account) use ($start, $end) {
                /**
                 * The balance for today always incorporates transactions
                 * made on today. So to get todays "start" balance, we sub one
                 * day.
                 */
                $yesterday = clone $start;
                $yesterday->subDay();

                /** @noinspection PhpParamsInspection */
                $account->startBalance = Steam::balance($account, $yesterday);
                $account->endBalance   = Steam::balance($account, $end);
            }
        );


        // summarize:
        foreach ($accounts as $account) {
            $startAmount = bcadd($startAmount, $account->startBalance);
            $endAmount   = bcadd($endAmount, $account->endBalance);
            $diff        = bcadd($diff, bcsub($account->endBalance, $account->startBalance));
        }

        $object = new AccountCollection;
        $object->setStart($startAmount);
        $object->setEnd($endAmount);
        $object->setDifference($diff);
        $object->setAccounts($accounts);

        return $object;
    }

    /**
     * Get a full report on the users incomes during the period for the given accounts.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return Income
     */
    public function getIncomeReport($start, $end, Collection $accounts)
    {
        $object = new Income;
        $set    = $this->query->incomeInPeriod($start, $end, $accounts);
        foreach ($set as $entry) {
            $object->addToTotal($entry->amount_positive);
            $object->addOrCreateIncome($entry);
        }

        return $object;
    }

    /**
     * Get a full report on the users expenses during the period for a list of accounts.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return Expense
     */
    public function getExpenseReport($start, $end, Collection $accounts)
    {
        $object = new Expense;
        $set    = $this->query->expenseInPeriod($start, $end, $accounts);
        foreach ($set as $entry) {
            $object->addToTotal($entry->amount); // can be positive, if it's a transfer
            $object->addOrCreateExpense($entry);
        }

        return $object;
    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return BudgetCollection
     */
    public function getBudgetReport(Carbon $start, Carbon $end, Collection $accounts)
    {
        $object = new BudgetCollection;
        /** @var \FireflyIII\Repositories\Budget\BudgetRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $set        = $repository->getBudgets();

        bcscale(2);

        foreach ($set as $budget) {

            $repetitions = $repository->getBudgetLimitRepetitions($budget, $start, $end);

            // no repetition(s) for this budget:
            if ($repetitions->count() == 0) {
                $spent      = $repository->balanceInPeriod($budget, $start, $end, $accounts);
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
                $expenses = $repository->balanceInPeriod($budget, $start, $end, $accounts);

                // 200 en -100 is 100, vergeleken met 0 === 1
                // 200 en -200 is 0, vergeleken met 0 === 0
                // 200 en -300 is -100, vergeleken met 0 === -1

                $left      = bccomp(bcadd($repetition->amount, $expenses), '0') === 1 ? bcadd($repetition->amount, $expenses) : 0;
                $spent     = bccomp(bcadd($repetition->amount, $expenses), '0') === 1 ? $expenses : '0';
                $overspent = bccomp(bcadd($repetition->amount, $expenses), '0') === 1 ? '0' : bcadd($expenses, $repetition->amount);

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
        $budgetLine->setSpent($noBudget);
        $object->addOverspent($noBudget);
        $object->addBudgetLine($budgetLine);

        return $object;
    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return Balance
     */
    public function getBalanceReport(Carbon $start, Carbon $end, Collection $accounts)
    {
        $repository    = app('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $tagRepository = app('FireflyIII\Repositories\Tag\TagRepositoryInterface');
        $balance       = new Balance;

        // build a balance header:
        $header  = new BalanceHeader;
        $budgets = $repository->getBudgets();
        foreach ($accounts as $account) {
            $header->addAccount($account);
        }

        /** @var BudgetModel $budget */
        foreach ($budgets as $budget) {
            $line = new BalanceLine;
            $line->setBudget($budget);

            // get budget amount for current period:
            $rep = $repository->getCurrentRepetition($budget, $start, $end);
            // could be null?
            $line->setRepetition($rep);

            // loop accounts:
            foreach ($accounts as $account) {
                $balanceEntry = new BalanceEntry;
                $balanceEntry->setAccount($account);

                // get spent:
                $spent = $this->query->spentInBudget($account, $budget, $start, $end); // I think shared is irrelevant.

                $balanceEntry->setSpent($spent);
                $line->addBalanceEntry($balanceEntry);
            }
            // add line to balance:
            $balance->addBalanceLine($line);
        }

        // then a new line for without budget.
        // and one for the tags:
        // and one for "left unbalanced".
        $empty    = new BalanceLine;
        $tags     = new BalanceLine;
        $diffLine = new BalanceLine;

        $tags->setRole(BalanceLine::ROLE_TAGROLE);
        $diffLine->setRole(BalanceLine::ROLE_DIFFROLE);

        foreach ($accounts as $account) {
            $spent = $this->query->spentNoBudget($account, $start, $end);
            $left  = $tagRepository->coveredByBalancingActs($account, $start, $end);
            bcscale(2);
            $diff = bcadd($spent, $left);

            // budget
            $budgetEntry = new BalanceEntry;
            $budgetEntry->setAccount($account);
            $budgetEntry->setSpent($spent);
            $empty->addBalanceEntry($budgetEntry);

            // balanced by tags
            $tagEntry = new BalanceEntry;
            $tagEntry->setAccount($account);
            $tagEntry->setLeft($left);
            $tags->addBalanceEntry($tagEntry);

            // difference:
            $diffEntry = new BalanceEntry;
            $diffEntry->setAccount($account);
            $diffEntry->setSpent($diff);
            $diffLine->addBalanceEntry($diffEntry);

        }

        $balance->addBalanceLine($empty);
        $balance->addBalanceLine($tags);
        $balance->addBalanceLine($diffLine);

        $balance->setBalanceHeader($header);

        return $balance;
    }

    /**
     * This method generates a full report for the given period on all
     * the users bills and their payments.
     *
     * Excludes bills which have not had a payment on the mentioned accounts.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return BillCollection
     */
    public function getBillReport(Carbon $start, Carbon $end, Collection $accounts)
    {
        /** @var \FireflyIII\Repositories\Bill\BillRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $bills      = $repository->getBillsForAccounts($accounts);
        $collection = new BillCollection;

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            $billLine = new BillLine;
            $billLine->setBill($bill);
            $billLine->setActive(intval($bill->active) == 1);
            $billLine->setMin($bill->amount_min);
            $billLine->setMax($bill->amount_max);

            // is hit in period?
            bcscale(2);
            $set = $repository->getJournalsInRange($bill, $start, $end);
            if ($set->count() == 0) {
                $billLine->setHit(false);
            } else {
                $billLine->setHit(true);
                $amount = '0';
                foreach ($set as $entry) {
                    $amount = bcadd($amount, $entry->amount);
                }
                $billLine->setAmount($amount);
            }

            $collection->addBill($billLine);

        }

        return $collection;
    }
}
