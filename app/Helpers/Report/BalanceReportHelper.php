<?php
/**
 * BalanceReportHelper.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Balance;
use FireflyIII\Helpers\Collection\BalanceEntry;
use FireflyIII\Helpers\Collection\BalanceHeader;
use FireflyIII\Helpers\Collection\BalanceLine;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class BalanceReportHelper
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) // I can't really help it.
 * @package FireflyIII\Helpers\Report
 */
class BalanceReportHelper implements BalanceReportHelperInterface
{

    /** @var  BudgetRepositoryInterface */
    protected $budgetRepository;

    /**
     * ReportHelper constructor.
     *
     *
     * @param BudgetRepositoryInterface $budgetRepository
     */
    public function __construct(BudgetRepositoryInterface $budgetRepository)
    {
        $this->budgetRepository = $budgetRepository;
    }


    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Balance
     */
    public function getBalanceReport(Collection $accounts, Carbon $start, Carbon $end): Balance
    {
        Log::debug('Start of balance report');
        $balance      = new Balance;
        $header       = new BalanceHeader;
        $budgetLimits = $this->budgetRepository->getAllBudgetLimits($start, $end);
        foreach ($accounts as $account) {
            Log::debug(sprintf('Add account %s to headers.', $account->name));
            $header->addAccount($account);
        }

        /** @var BudgetLimit $budgetLimit */
        foreach ($budgetLimits as $budgetLimit) {
            if (!is_null($budgetLimit->budget)) {
                $line = $this->createBalanceLine($budgetLimit, $accounts);
                $balance->addBalanceLine($line);
            }
        }
        $noBudgetLine = $this->createNoBudgetLine($accounts, $start, $end);

        $balance->addBalanceLine($noBudgetLine);
        $balance->setBalanceHeader($header);

        Log::debug('Clear unused budgets.');
        // remove budgets without expenses from balance lines:
        $balance = $this->removeUnusedBudgets($balance);

        Log::debug('Return report.');

        return $balance;
    }


    /**
     * @param BudgetLimit $budgetLimit
     * @param Collection  $accounts
     *
     * @return BalanceLine
     */
    private function createBalanceLine(BudgetLimit $budgetLimit, Collection $accounts): BalanceLine
    {
        $line = new BalanceLine;
        $line->setBudget($budgetLimit->budget);
        $line->setBudgetLimit($budgetLimit);

        // loop accounts:
        foreach ($accounts as $account) {
            $balanceEntry = new BalanceEntry;
            $balanceEntry->setAccount($account);
            $spent = $this->budgetRepository->spentInPeriod(
                new Collection([$budgetLimit->budget]), new Collection([$account]), $budgetLimit->start_date, $budgetLimit->end_date
            );
            $balanceEntry->setSpent($spent);
            $line->addBalanceEntry($balanceEntry);
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
            $spent = $this->budgetRepository->spentInPeriodWoBudget(new Collection([$account]), $start, $end);
            // budget
            $budgetEntry = new BalanceEntry;
            $budgetEntry->setAccount($account);
            $budgetEntry->setSpent($spent);
            $empty->addBalanceEntry($budgetEntry);

        }

        return $empty;
    }


    /**
     * @param Balance $balance
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's exactly 5.
     *
     * @return Balance
     */
    private function removeUnusedBudgets(Balance $balance): Balance
    {
        $set    = $balance->getBalanceLines();
        $newSet = new Collection;
        foreach ($set as $entry) {
            if (!is_null($entry->getBudget()->id)) {
                $sum = '0';
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
