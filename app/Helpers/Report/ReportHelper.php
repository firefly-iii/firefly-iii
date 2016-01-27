<?php

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
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
use FireflyIII\Helpers\FiscalHelper;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Budget as BudgetModel;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class ReportHelper
 *
 * @package FireflyIII\Helpers\Report
 */
class ReportHelper implements ReportHelperInterface
{

    /** @var  BudgetRepositoryInterface */
    protected $budgetRepository;
    /** @var ReportQueryInterface */
    protected $query;
    /** @var  TagRepositoryInterface */
    protected $tagRepository;

    /**
     * ReportHelper constructor.
     *
     * @codeCoverageIgnore
     *
     * @param ReportQueryInterface      $query
     * @param BudgetRepositoryInterface $budgetRepository
     * @param TagRepositoryInterface    $tagRepository
     */
    public function __construct(ReportQueryInterface $query, BudgetRepositoryInterface $budgetRepository, TagRepositoryInterface $tagRepository)
    {
        $this->query            = $query;
        $this->budgetRepository = $budgetRepository;
        $this->tagRepository    = $tagRepository;
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
        $balance = new Balance;

        // build a balance header:
        $header    = new BalanceHeader;
        $budgets   = $this->budgetRepository->getBudgetsAndLimitsInRange($start, $end);
        $spentData = $this->budgetRepository->spentPerBudgetPerAccount($budgets, $accounts, $start, $end);
        foreach ($accounts as $account) {
            $header->addAccount($account);
        }

        /** @var BudgetModel $budget */
        foreach ($budgets as $budget) {
            $balance->addBalanceLine($this->createBalanceLine($budget, $accounts, $spentData));
        }

        $balance->addBalanceLine($this->createEmptyBalanceLine($accounts, $spentData));
        $balance->addBalanceLine($this->createTagsBalanceLine($accounts, $start, $end));
        $balance->addBalanceLine($this->createDifferenceBalanceLine($accounts, $spentData, $start, $end));

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
        $journals   = $repository->getAllJournalsInRange($bills, $start, $end);
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

            $entry = $journals->filter(
                function (TransactionJournal $journal) use ($bill) {
                    return $journal->bill_id == $bill->id;
                }
            );
            if (!is_null($entry->first())) {
                $billLine->setAmount($entry->first()->journalAmount);
                $billLine->setHit(true);
            } else {
                $billLine->setHit(false);
            }

            $collection->addBill($billLine);

        }

        return $collection;
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

        $set = $repository->spentForAccountsPerMonth($accounts, $start, $end);
        foreach ($set as $category) {
            $object->addCategory($category);
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
        $set    = $this->query->expense($accounts, $start, $end);

        foreach ($set as $entry) {
            $object->addToTotal($entry->journalAmount); // can be positive, if it's a transfer
            $object->addOrCreateExpense($entry);
        }

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
        $set    = $this->query->income($accounts, $start, $end);

        foreach ($set as $entry) {
            $object->addToTotal($entry->journalAmount);
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

        $start        = clone $date;
        $end          = Carbon::now();
        $months       = [];
        $fiscalHelper = new FiscalHelper;
        while ($start <= $end) {
            $year = $fiscalHelper->endOfFiscalYear($start)->year;

            if (!isset($months[$year])) {
                $months[$year] = [
                    'start'  => $fiscalHelper->startOfFiscalYear($start)->format('Y-m-d'),
                    'end'    => $fiscalHelper->endOfFiscalYear($start)->format('Y-m-d'),
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
     * Take the array as returned by SingleCategoryRepositoryInterface::spentPerDay and SingleCategoryRepositoryInterface::earnedByDay
     * and sum up everything in the array in the given range.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param array  $array
     *
     * @return string
     */
    protected function getSumOfRange(Carbon $start, Carbon $end, array $array)
    {
        bcscale(2);
        $sum          = '0';
        $currentStart = clone $start; // to not mess with the original one
        $currentEnd   = clone $end; // to not mess with the original one

        while ($currentStart <= $currentEnd) {
            $date = $currentStart->format('Y-m-d');
            if (isset($array[$date])) {
                $sum = bcadd($sum, $array[$date]);
            }
            $currentStart->addDay();
        }

        return $sum;
    }

    /**
     * @param Budget     $budget
     * @param Collection $accounts
     * @param Collection $spentData
     *
     * @return BalanceLine
     */
    private function createBalanceLine(BudgetModel $budget, Collection $accounts, Collection $spentData)
    {
        $line = new BalanceLine;
        $line->setBudget($budget);

        // loop accounts:
        foreach ($accounts as $account) {
            $balanceEntry = new BalanceEntry;
            $balanceEntry->setAccount($account);

            // get spent:
            $entry = $spentData->filter(
                function (TransactionJournal $model) use ($budget, $account) {
                    return $model->account_id == $account->id && $model->budget_id == $budget->id;
                }
            );
            $spent = 0;
            if (!is_null($entry->first())) {
                $spent = $entry->first()->spent;
            }
            $balanceEntry->setSpent($spent);
            $line->addBalanceEntry($balanceEntry);
        }

        return $line;
    }

    /**
     * @param Collection $accounts
     * @param Collection $spentData
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return BalanceLine
     */
    private function createDifferenceBalanceLine(Collection $accounts, Collection $spentData, Carbon $start, Carbon $end)
    {
        $diff     = new BalanceLine;
        $tagsLeft = $this->tagRepository->allCoveredByBalancingActs($accounts, $start, $end);

        $diff->setRole(BalanceLine::ROLE_DIFFROLE);

        foreach ($accounts as $account) {
            $entry = $spentData->filter(
                function (TransactionJournal $model) use ($account) {
                    return $model->account_id == $account->id && is_null($model->budget_id);
                }
            );
            $spent = 0;
            if (!is_null($entry->first())) {
                $spent = $entry->first()->spent;
            }
            $leftEntry = $tagsLeft->filter(
                function (Tag $tag) use ($account) {
                    return $tag->account_id == $account->id;
                }
            );
            $left      = 0;
            if (!is_null($leftEntry->first())) {
                $left = $leftEntry->first()->sum;
            }
            bcscale(2);
            $diffValue = bcadd($spent, $left);

            // difference:
            $diffEntry = new BalanceEntry;
            $diffEntry->setAccount($account);
            $diffEntry->setSpent($diffValue);
            $diff->addBalanceEntry($diffEntry);

        }

        return $diff;
    }

    /**
     * @param Collection $accounts
     * @param Collection $spentData
     *
     * @return BalanceLine
     */
    private function createEmptyBalanceLine(Collection $accounts, Collection $spentData)
    {
        $empty = new BalanceLine;

        foreach ($accounts as $account) {
            $entry = $spentData->filter(
                function (TransactionJournal $model) use ($account) {
                    return $model->account_id == $account->id && is_null($model->budget_id);
                }
            );
            $spent = 0;
            if (!is_null($entry->first())) {
                $spent = $entry->first()->spent;
            }

            // budget
            $budgetEntry = new BalanceEntry;
            $budgetEntry->setAccount($account);
            $budgetEntry->setSpent($spent);
            $empty->addBalanceEntry($budgetEntry);

        }

        return $empty;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return BalanceLine
     */
    private function createTagsBalanceLine(Collection $accounts, Carbon $start, Carbon $end)
    {
        $tags     = new BalanceLine;
        $tagsLeft = $this->tagRepository->allCoveredByBalancingActs($accounts, $start, $end);

        $tags->setRole(BalanceLine::ROLE_TAGROLE);

        foreach ($accounts as $account) {
            $leftEntry = $tagsLeft->filter(
                function (Tag $tag) use ($account) {
                    return $tag->account_id == $account->id;
                }
            );
            $left      = 0;
            if (!is_null($leftEntry->first())) {
                $left = $leftEntry->first()->sum;
            }
            bcscale(2);

            // balanced by tags
            $tagEntry = new BalanceEntry;
            $tagEntry->setAccount($account);
            $tagEntry->setLeft($left);
            $tags->addBalanceEntry($tagEntry);

        }

        return $tags;
    }
}
