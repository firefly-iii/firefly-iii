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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Balance;
use FireflyIII\Helpers\Collection\BalanceEntry;
use FireflyIII\Helpers\Collection\BalanceHeader;
use FireflyIII\Helpers\Collection\BalanceLine;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class BalanceReportHelper.
 *
 * @codeCoverageIgnore
 */
class BalanceReportHelper implements BalanceReportHelperInterface
{
    /** @var BudgetRepositoryInterface Budget repository */
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

        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }

    }

    /**
     * Generate a balance report.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function getBalanceReport(Collection $accounts, Carbon $start, Carbon $end): array
    {
        Log::debug('Start of balance report');
        $report = [
            'budgets'  => [],
            'accounts' => [],
        ];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $report['accounts'][$account->id] = [
                'id'   => $account->id,
                'name' => $account->name,
                'iban' => $account->iban,
                'sum'  => '0',
            ];
        }

        $budgets = $this->budgetRepository->getBudgets();
        // per budget, dan per balance line
        // of als het in een balance line valt dan daaronder en anders niet
        // kruistabel vullen?

        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $budgetId                     = $budget->id;
            $report['budgets'][$budgetId] = [
                'budget_id'   => $budgetId,
                'budget_name' => $budget->name,
                'spent'       => [], // per account
                'sums'        => [], // per currency
            ];
            $spent                        = [];
            /** @var GroupCollectorInterface $collector */
            $collector = app(GroupCollectorInterface::class);
            $journals  = $collector->setRange($start, $end)->setSourceAccounts($accounts)->setTypes([TransactionType::WITHDRAWAL])->setBudget($budget)
                                   ->getExtractedJournals();
            /** @var array $journal */
            foreach ($journals as $journal) {
                $sourceAccount                  = $journal['source_account_id'];
                $currencyId                     = $journal['currency_id'];
                $spent[$sourceAccount]          = $spent[$sourceAccount] ?? [
                        'source_account_id'       => $sourceAccount,
                        'currency_id'             => $journal['currency_id'],
                        'currency_code'           => $journal['currency_code'],
                        'currency_name'           => $journal['currency_name'],
                        'currency_symbol'         => $journal['currency_symbol'],
                        'currency_decimal_places' => $journal['currency_decimal_places'],
                        'spent'                   => '0',
                    ];
                $spent[$sourceAccount]['spent'] = bcadd($spent[$sourceAccount]['spent'], $journal['amount']);

                // also fix sum:
                $report['sums'][$budgetId][$currencyId]        = $report['sums'][$budgetId][$currencyId] ?? [
                        'sum'                     => '0',
                        'currency_id'             => $journal['currency_id'],
                        'currency_code'           => $journal['currency_code'],
                        'currency_name'           => $journal['currency_name'],
                        'currency_symbol'         => $journal['currency_symbol'],
                        'currency_decimal_places' => $journal['currency_decimal_places'],
                    ];
                $report['sums'][$budgetId][$currencyId]['sum'] = bcadd($report['sums'][$budgetId][$currencyId]['sum'], $journal['amount']);
                $report['accounts'][$sourceAccount]['sum']     = bcadd($report['accounts'][$sourceAccount]['sum'], $journal['amount']);

                // add currency info for account sum
                $report['accounts'][$sourceAccount]['currency_id']             = $journal['currency_id'];
                $report['accounts'][$sourceAccount]['currency_code']           = $journal['currency_code'];
                $report['accounts'][$sourceAccount]['currency_name']           = $journal['currency_name'];
                $report['accounts'][$sourceAccount]['currency_symbol']         = $journal['currency_symbol'];
                $report['accounts'][$sourceAccount]['currency_decimal_places'] = $journal['currency_decimal_places'];
            }
            $report['budgets'][$budgetId]['spent'] = $spent;
            // get transactions in budget
        }

        return $report;
        // do sums:


        echo '<pre>';
        print_r($report);
        exit;


        $balance      = new Balance;
        $header       = new BalanceHeader;
        $budgetLimits = $this->budgetRepository->getAllBudgetLimits($start, $end);
        foreach ($accounts as $account) {
            Log::debug(sprintf('Add account %s to headers.', $account->name));
            $header->addAccount($account);
        }

        /** @var BudgetLimit $budgetLimit */
        foreach ($budgetLimits as $budgetLimit) {
            if (null !== $budgetLimit->budget) {
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
     * Create one balance line.
     *
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
                new Collection([$budgetLimit->budget]),
                new Collection([$account]),
                $budgetLimit->start_date,
                $budgetLimit->end_date
            );
            $balanceEntry->setSpent($spent);
            $line->addBalanceEntry($balanceEntry);
        }

        return $line;
    }

    /**
     * Create a line for transactions without a budget.
     *
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
     * Remove unused budgets from the report.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
            if (null !== $entry->getBudget()->id) {
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
