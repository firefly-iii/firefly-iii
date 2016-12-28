<?php
/**
 * MonthReportGenerator.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Generator\Report\Budget;


use Carbon\Carbon;
use FireflyIII\Generator\Report\ReportGeneratorInterface;
use FireflyIII\Generator\Report\Support;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;
use Log;

/**
 * Class MonthReportGenerator
 *
 * @package FireflyIII\Generator\Report\Budget
 */
class MonthReportGenerator extends Support implements ReportGeneratorInterface
{
    /** @var  Collection */
    private $accounts;
    /** @var  Collection */
    private $budgets;
    /** @var  Carbon */
    private $end;
    /** @var Collection */
    private $expenses;
    /** @var Collection */
    private $income;
    /** @var  Carbon */
    private $start;

    /**
     * MonthReportGenerator constructor.
     */
    public function __construct()
    {
        $this->income   = new Collection;
        $this->expenses = new Collection;
    }

    /**
     * @return string
     */
    public function generate(): string
    {
        $accountIds      = join(',', $this->accounts->pluck('id')->toArray());
        $budgetIds       = join(',', $this->budgets->pluck('id')->toArray());
        $expenses        = $this->getExpenses();
        $accountSummary  = $this->summarizeByAccount($expenses);
        $budgetSummary   = $this->summarizeByBudget($expenses);
        $averageExpenses = $this->getAverages($expenses, SORT_ASC);
        $topExpenses     = $this->getTopExpenses();

        // render!
        return view('reports.budget.month', compact('accountIds', 'budgetIds', 'accountSummary', 'budgetSummary', 'averageExpenses', 'topExpenses'))
            ->with('start', $this->start)->with('end', $this->end)
            ->with('budgets', $this->budgets)
            ->with('accounts', $this->accounts)
            ->render();
    }

    /**
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
     * @param Collection $categories
     *
     * @return ReportGeneratorInterface
     */
    public function setCategories(Collection $categories): ReportGeneratorInterface
    {
        return $this;
    }

    /**
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
     * @param Collection $collection
     * @param int        $sortFlag
     *
     * @return array
     */
    private function getAverages(Collection $collection, int $sortFlag): array
    {
        $result = [];
        /** @var Transaction $transaction */
        foreach ($collection as $transaction) {
            // opposing name and ID:
            $opposingId = $transaction->opposing_account_id;

            // is not set?
            if (!isset($result[$opposingId])) {
                $name                = $transaction->opposing_account_name;
                $result[$opposingId] = [
                    'name'    => $name,
                    'count'   => 1,
                    'id'      => $opposingId,
                    'average' => $transaction->transaction_amount,
                    'sum'     => $transaction->transaction_amount,
                ];
                continue;
            }
            $result[$opposingId]['count']++;
            $result[$opposingId]['sum']     = bcadd($result[$opposingId]['sum'], $transaction->transaction_amount);
            $result[$opposingId]['average'] = bcdiv($result[$opposingId]['sum'], strval($result[$opposingId]['count']));
        }

        // sort result by average:
        $average = [];
        foreach ($result as $key => $row) {
            $average[$key] = floatval($row['average']);
        }

        array_multisort($average, $sortFlag, $result);

        return $result;
    }

    /**
     * @return Collection
     */
    private function getExpenses(): Collection
    {
        if ($this->expenses->count() > 0) {
            Log::debug('Return previous set of expenses.');

            return $this->expenses;
        }

        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class, [auth()->user()]);
        $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)
                  ->setTypes([TransactionType::WITHDRAWAL])
                  ->setBudgets($this->budgets)->withOpposingAccount()->disableFilter();

        $accountIds     = $this->accounts->pluck('id')->toArray();
        $transactions   = $collector->getJournals();
        $transactions   = self::filterExpenses($transactions, $accountIds);
        $this->expenses = $transactions;

        return $transactions;
    }

    /**
     * @return Collection
     */
    private function getTopExpenses(): Collection
    {
        $transactions = $this->getExpenses()->sortBy('transaction_amount');

        return $transactions;
    }

    /**
     * @param Collection $collection
     *
     * @return array
     */
    private function summarizeByAccount(Collection $collection): array
    {
        $result = [];
        /** @var Transaction $transaction */
        foreach ($collection as $transaction) {
            $accountId          = $transaction->account_id;
            $result[$accountId] = $result[$accountId] ?? '0';
            $result[$accountId] = bcadd($transaction->transaction_amount, $result[$accountId]);
        }

        return $result;
    }

    /**
     * @param Collection $collection
     *
     * @return array
     */
    private function summarizeByBudget(Collection $collection): array
    {
        $result = [];
        /** @var Transaction $transaction */
        foreach ($collection as $transaction) {
            $jrnlBudId         = intval($transaction->transaction_journal_budget_id);
            $transBudId        = intval($transaction->transaction_budget_id);
            $budgetId          = max($jrnlBudId, $transBudId);
            $result[$budgetId] = $result[$budgetId] ?? '0';
            $result[$budgetId] = bcadd($transaction->transaction_amount, $result[$budgetId]);
        }

        return $result;
    }
}
