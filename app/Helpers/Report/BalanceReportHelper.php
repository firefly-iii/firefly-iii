<?php
declare(strict_types = 1);
/**
 * BalanceReportHelper.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Helpers\Report;

use Auth;
use Carbon\Carbon;
use DB;
use FireflyIII\Helpers\Collection\Balance;
use FireflyIII\Helpers\Collection\BalanceEntry;
use FireflyIII\Helpers\Collection\BalanceHeader;
use FireflyIII\Helpers\Collection\BalanceLine;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Budget as BudgetModel;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

/**
 * Class BalanceReportHelper
 *
 * @package FireflyIII\Helpers\Report
 */
class BalanceReportHelper implements BalanceReportHelperInterface
{

    /** @var  BudgetRepositoryInterface */
    protected $budgetRepository;
    /** @var  TagRepositoryInterface */
    protected $tagRepository;

    /**
     * ReportHelper constructor.
     *
     *
     * @param BudgetRepositoryInterface $budgetRepository
     * @param TagRepositoryInterface    $tagRepository
     */
    public function __construct(BudgetRepositoryInterface $budgetRepository, TagRepositoryInterface $tagRepository)
    {
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
    public function getBalanceReport(Carbon $start, Carbon $end, Collection $accounts): Balance
    {
        $balance = new Balance;
        // build a balance header:
        $header           = new BalanceHeader;
        $limitRepetitions = $this->budgetRepository->getAllBudgetLimitRepetitions($start, $end);
        foreach ($accounts as $account) {
            $header->addAccount($account);
        }

        /** @var LimitRepetition $repetition */
        foreach ($limitRepetitions as $repetition) {
            $budget = $this->budgetRepository->find($repetition->budget_id);
            $balance->addBalanceLine($this->createBalanceLine($budget, $repetition, $accounts));
        }
        $noBudgetLine       = $this->createNoBudgetLine($accounts, $start, $end);
        $coveredByTagLine   = $this->createTagsBalanceLine($accounts, $start, $end);
        $leftUnbalancedLine = $this->createLeftUnbalancedLine($noBudgetLine, $coveredByTagLine);

        $balance->addBalanceLine($noBudgetLine);
        $balance->addBalanceLine($coveredByTagLine);
        $balance->addBalanceLine($leftUnbalancedLine);
        $balance->setBalanceHeader($header);

        // remove budgets without expenses from balance lines:
        $balance = $this->removeUnusedBudgets($balance);

        return $balance;
    }

    /**
     * This method collects all transfers that are part of a "balancing act" tag
     * and groups the amounts of those transfers by their destination account.
     *
     * This is used to indicate which expenses, usually outside of budgets, have been
     * corrected by transfers from a savings account.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    private function allCoveredByBalancingActs(Collection $accounts, Carbon $start, Carbon $end): Collection
    {
        $ids = $accounts->pluck('id')->toArray();
        $set = Auth::user()->tags()
                   ->leftJoin('tag_transaction_journal', 'tag_transaction_journal.tag_id', '=', 'tags.id')
                   ->leftJoin('transaction_journals', 'tag_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                   ->leftJoin('transaction_types', 'transaction_journals.transaction_type_id', '=', 'transaction_types.id')
                   ->leftJoin(
                       'transactions AS t_source', function (JoinClause $join) {
                       $join->on('transaction_journals.id', '=', 't_source.transaction_journal_id')->where('t_source.amount', '<', 0);
                   }
                   )
                   ->leftJoin(
                       'transactions AS t_destination', function (JoinClause $join) {
                       $join->on('transaction_journals.id', '=', 't_destination.transaction_journal_id')->where('t_destination.amount', '>', 0);
                   }
                   )
                   ->where('tags.tagMode', 'balancingAct')
                   ->where('transaction_types.type', TransactionType::TRANSFER)
                   ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                   ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                   ->whereNull('transaction_journals.deleted_at')
                   ->whereIn('t_source.account_id', $ids)
                   ->whereIn('t_destination.account_id', $ids)
                   ->groupBy('t_destination.account_id')
                   ->get(
                       [
                           't_destination.account_id',
                           DB::raw('SUM(`t_destination`.`amount`) as `sum`'),
                       ]
                   );

        return $set;
    }


    /**
     * @param Budget          $budget
     * @param LimitRepetition $repetition
     * @param Collection      $accounts
     *
     * @return BalanceLine
     */
    private function createBalanceLine(BudgetModel $budget, LimitRepetition $repetition, Collection $accounts): BalanceLine
    {
        $line           = new BalanceLine;
        $budget->amount = $repetition->amount;
        $line->setBudget($budget);


        $line->setStartDate($repetition->startdate);
        $line->setEndDate($repetition->enddate);

        // loop accounts:
        foreach ($accounts as $account) {
            $balanceEntry = new BalanceEntry;
            $balanceEntry->setAccount($account);
            $spent = $this->budgetRepository->spentInPeriod(
                new Collection([$budget]), new Collection([$account]), $repetition->startdate, $repetition->enddate
            );
            $balanceEntry->setSpent($spent);
            $line->addBalanceEntry($balanceEntry);
        }

        return $line;
    }

    /**
     * @param BalanceLine $noBudgetLine
     * @param BalanceLine $coveredByTagLine
     *
     * @return BalanceLine
     */
    private function createLeftUnbalancedLine(BalanceLine $noBudgetLine, BalanceLine $coveredByTagLine): BalanceLine
    {
        $line = new BalanceLine;
        $line->setRole(BalanceLine::ROLE_DIFFROLE);
        $noBudgetEntries = $noBudgetLine->getBalanceEntries();
        $tagEntries      = $coveredByTagLine->getBalanceEntries();

        /** @var BalanceEntry $entry */
        foreach ($noBudgetEntries as $entry) {
            $account  = $entry->getAccount();
            $tagEntry = $tagEntries->filter(
                function (BalanceEntry $current) use ($account) {
                    return $current->getAccount()->id === $account->id;
                }
            );
            if ($tagEntry->first()) {
                // found corresponding entry. As we should:
                $newEntry = new BalanceEntry;
                $newEntry->setAccount($account);
                $spent = bcadd($tagEntry->first()->getLeft(), $entry->getSpent());
                $newEntry->setSpent($spent);
                $line->addBalanceEntry($newEntry);
            }
        }

        return $line;


    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return BalanceLine
     */
    private function createNoBudgetLine(Collection $accounts, Carbon $start, Carbon $end): BalanceLine
    {
        $empty = new BalanceLine;

        foreach ($accounts as $account) {
            $spent = $this->budgetRepository->spentInPeriodWithoutBudget(new Collection([$account]), $start, $end);
            //$spent ='0';
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
    private function createTagsBalanceLine(Collection $accounts, Carbon $start, Carbon $end): BalanceLine
    {
        $tags     = new BalanceLine;
        $tagsLeft = $this->allCoveredByBalancingActs($accounts, $start, $end);

        $tags->setRole(BalanceLine::ROLE_TAGROLE);

        foreach ($accounts as $account) {
            $leftEntry = $tagsLeft->filter(
                function (Tag $tag) use ($account) {
                    return $tag->account_id == $account->id;
                }
            );
            $left      = '0';
            if (!is_null($leftEntry->first())) {
                $left = $leftEntry->first()->sum;
            }

            // balanced by tags
            $tagEntry = new BalanceEntry;
            $tagEntry->setAccount($account);
            $tagEntry->setLeft($left);
            $tags->addBalanceEntry($tagEntry);

        }

        return $tags;
    }

    /**
     * @param Balance $balance
     *
     * @return Balance
     */
    private function removeUnusedBudgets(Balance $balance): Balance
    {
        $set    = $balance->getBalanceLines();
        $newSet = new Collection;
        /** @var BalanceLine $entry */
        foreach ($set as $entry) {
            if (!is_null($entry->getBudget()->id)) {
                $sum = '0';
                /** @var BalanceEntry $balanceEntry */
                foreach ($entry->getBalanceEntries() as $balanceEntry) {
                    $sum = bcadd($sum, $balanceEntry->getSpent());
                }
                if (bccomp($sum, '0') === -1) {
                    $newSet->push($entry);
                }
                continue;
            }
            $newSet->push($entry);
        }

        $balance->setBalanceLines($newSet);

        return $balance;

    }

}
