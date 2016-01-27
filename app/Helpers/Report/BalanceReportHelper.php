<?php
/**
 * BalanceReportHelper.php
 * Copyright (C) 2016 Sander Dorigo
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
     * @codeCoverageIgnore
     *
     * @param ReportQueryInterface      $query
     * @param BudgetRepositoryInterface $budgetRepository
     * @param TagRepositoryInterface    $tagRepository
     */
    public function __construct(ReportQueryInterface $query, BudgetRepositoryInterface $budgetRepository, TagRepositoryInterface $tagRepository)
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