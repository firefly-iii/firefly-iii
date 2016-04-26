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

use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Balance;
use FireflyIII\Helpers\Collection\BalanceEntry;
use FireflyIII\Helpers\Collection\BalanceHeader;
use FireflyIII\Helpers\Collection\BalanceLine;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Budget as BudgetModel;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
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
        $set = $this->user->tags()
                          ->leftJoin('tag_transaction_journal', 'tag_transaction_journal.tag_id', '=', 'tags.id')
                          ->leftJoin('transaction_journals', 'tag_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                          ->leftJoin('transaction_types', 'transaction_journals.transaction_type_id', '=', 'transaction_types.id')
                          ->leftJoin(
                              'transactions AS t_source', function (JoinClause $join) {
                              $join->on('transaction_journals.id', '=', 't_source.transaction_journal_id')->where('t_source.amount', '<', 0);
                          }
                          )
                          ->leftJoin(
                              'transactions AS t_to', function (JoinClause $join) {
                              $join->on('transaction_journals.id', '=', 't_to.transaction_journal_id')->where('t_to.amount', '>', 0);
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
     * @param Budget     $budget
     * @param Collection $accounts
     * @param Collection $spentData
     *
     * @return BalanceLine
     */
    private function createBalanceLine(BudgetModel $budget, Collection $accounts, Collection $spentData): BalanceLine
    {
        $line = new BalanceLine;
        $line->setBudget($budget);
        $line->setStartDate($budget->startdate); // returned by getBudgetsAndLimitsInRange()
        $line->setEndDate($budget->enddate); // returned by getBudgetsAndLimitsInRange()

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
            $spent = '0';
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
    private function createDifferenceBalanceLine(Collection $accounts, Collection $spentData, Carbon $start, Carbon $end): BalanceLine
    {
        $diff     = new BalanceLine;
        $tagsLeft = $this->allCoveredByBalancingActs($accounts, $start, $end);

        $diff->setRole(BalanceLine::ROLE_DIFFROLE);

        foreach ($accounts as $account) {
            $entry = $spentData->filter(
                function (TransactionJournal $model) use ($account) {
                    return $model->account_id == $account->id && is_null($model->budget_id);
                }
            );
            $spent = '0';
            if (!is_null($entry->first())) {
                $spent = $entry->first()->spent;
            }
            $leftEntry = $tagsLeft->filter(
                function (Tag $tag) use ($account) {
                    return $tag->account_id == $account->id;
                }
            );
            $left      = '0';
            if (!is_null($leftEntry->first())) {
                $left = $leftEntry->first()->sum;
            }
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
    private function createEmptyBalanceLine(Collection $accounts, Collection $spentData): BalanceLine
    {
        $empty = new BalanceLine;

        foreach ($accounts as $account) {
            $entry = $spentData->filter(
                function (TransactionJournal $model) use ($account) {
                    return $model->account_id == $account->id && is_null($model->budget_id);
                }
            );
            $spent = '0';
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

}
