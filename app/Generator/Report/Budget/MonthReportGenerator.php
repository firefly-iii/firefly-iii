<?php
/**
 * MonthReportGenerator.php
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
/** @noinspection MultipleReturnStatementsInspection */
/** @noinspection PhpUndefinedMethodInspection */
declare(strict_types=1);

namespace FireflyIII\Generator\Report\Budget;

use Carbon\Carbon;
use FireflyIII\Generator\Report\ReportGeneratorInterface;
use FireflyIII\Generator\Report\Support;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\OpposingAccountFilter;
use FireflyIII\Helpers\Filter\PositiveAmountFilter;
use FireflyIII\Helpers\Filter\TransferFilter;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;
use Log;
use Throwable;

/**
 * Class MonthReportGenerator.
 *
 * @codeCoverageIgnore
 */
class MonthReportGenerator extends Support implements ReportGeneratorInterface
{
    /** @var Collection The accounts in the report. */
    private $accounts;
    /** @var Collection The budgets in the report. */
    private $budgets;
    /** @var Carbon The end date. */
    private $end;
    /** @var Collection The expenses in the report. */
    private $expenses;
    /** @var Carbon The start date. */
    private $start;

    /**
     * MonthReportGenerator constructor.
     */
    public function __construct()
    {
        $this->expenses = new Collection;
    }

    /**
     * Generates the report.
     *
     * @return string
     */
    public function generate(): string
    {
        $accountIds      = implode(',', $this->accounts->pluck('id')->toArray());
        $budgetIds       = implode(',', $this->budgets->pluck('id')->toArray());
        $expenses        = $this->getExpenses();
        $accountSummary  = $this->summarizeByAccount($expenses);
        $budgetSummary   = $this->summarizeByBudget($expenses);
        $averageExpenses = $this->getAverages($expenses, SORT_ASC);
        $topExpenses     = $this->getTopExpenses();

        // render!
        try {
            $result = view('reports.budget.month', compact('accountIds', 'budgetIds', 'accountSummary', 'budgetSummary', 'averageExpenses', 'topExpenses'))
                ->with('start', $this->start)->with('end', $this->end)
                ->with('budgets', $this->budgets)
                ->with('accounts', $this->accounts)
                ->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Cannot render reports.account.report: %s', $e->getMessage()));
            $result = 'Could not render report view.';
        }

        return $result;
    }

    /**
     * Set the involved accounts.
     *
     * @param Collection $accounts
     *
     * @return ReportGeneratorInterface
     */
    public function setAccounts(Collection $accounts): ReportGeneratorInterface
    {
        $this->accounts = $accounts;

        return $this;
    }

    /**
     * Set the involved budgets.
     *
     * @param Collection $budgets
     *
     * @return ReportGeneratorInterface
     */
    public function setBudgets(Collection $budgets): ReportGeneratorInterface
    {
        $this->budgets = $budgets;

        return $this;
    }

    /**
     * Unused category setter.
     *
     * @param Collection $categories
     *
     * @return ReportGeneratorInterface
     */
    public function setCategories(Collection $categories): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Set the end date of the report.
     *
     * @param Carbon $date
     *
     * @return ReportGeneratorInterface
     */
    public function setEndDate(Carbon $date): ReportGeneratorInterface
    {
        $this->end = $date;

        return $this;
    }

    /**
     * Unused expense setter.
     *
     * @param Collection $expense
     *
     * @return ReportGeneratorInterface
     */
    public function setExpense(Collection $expense): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Set the start date of the report.
     *
     * @param Carbon $date
     *
     * @return ReportGeneratorInterface
     */
    public function setStartDate(Carbon $date): ReportGeneratorInterface
    {
        $this->start = $date;

        return $this;
    }

    /**
     * Unused tags setter.
     *
     * @param Collection $tags
     *
     * @return ReportGeneratorInterface
     */
    public function setTags(Collection $tags): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Get the expenses.
     *
     * @return Collection
     */
    protected function getExpenses(): Collection
    {
        if ($this->expenses->count() > 0) {
            Log::debug('Return previous set of expenses.');

            return $this->expenses;
        }

        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)
                  ->setTypes([TransactionType::WITHDRAWAL])
                  ->setBudgets($this->budgets)->withOpposingAccount();
        $collector->removeFilter(TransferFilter::class);

        $collector->addFilter(OpposingAccountFilter::class);
        $collector->addFilter(PositiveAmountFilter::class);

        $transactions   = $collector->getTransactions();
        $this->expenses = $transactions;

        return $transactions;
    }

    /**
     * Summarize a collection by its budget.
     *
     * @param Collection $collection
     *
     * @return array
     */
    private function summarizeByBudget(Collection $collection): array
    {
        $result = [
            'sum' => '0',
        ];
        /** @var Transaction $transaction */
        foreach ($collection as $transaction) {
            $jrnlBudId         = (int)$transaction->transaction_journal_budget_id;
            $transBudId        = (int)$transaction->transaction_budget_id;
            $budgetId          = max($jrnlBudId, $transBudId);
            $result[$budgetId] = $result[$budgetId] ?? '0';
            $result[$budgetId] = bcadd($transaction->transaction_amount, $result[$budgetId]);
            $result['sum']     = bcadd($result['sum'], $transaction->transaction_amount);
        }

        return $result;
    }
}
