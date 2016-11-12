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

namespace FireflyIII\Generator\Report\Category;


use Carbon\Carbon;
use FireflyIII\Generator\Report\ReportGeneratorInterface;
use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;
use Log;

/**
 * Class MonthReportGenerator
 *
 * @package FireflyIII\Generator\Report\Category
 */
class MonthReportGenerator extends Support implements ReportGeneratorInterface
{
    /** @var  Collection */
    private $accounts;
    /** @var  Collection */
    private $categories;
    /** @var  Carbon */
    private $end;
    /** @var  Carbon */
    private $start;

    /**
     * @return string
     */
    public function generate(): string
    {
        $accountIds      = join(',', $this->accounts->pluck('id')->toArray());
        $categoryIds     = join(',', $this->categories->pluck('id')->toArray());
        $reportType      = 'category';
        $accountSummary  = $this->getAccountSummary();
        $categorySummary = $this->getCategorySummary();

        // render!
        return view('reports.category.month', compact('accountIds', 'categoryIds', 'reportType', 'accountSummary', 'categorySummary'))
            ->with('start', $this->start)->with('end', $this->end)
            ->with('categories', $this->categories)
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
     * @param Collection $categories
     *
     * @return ReportGeneratorInterface
     */
    public function setCategories(Collection $categories): ReportGeneratorInterface
    {
        $this->categories = $categories;

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
     * @return array
     */
    private function getAccountSummary(): array
    {
        $spent  = $this->getSpentAccountSummary();
        $earned = $this->getEarnedAccountSummary();
        $return = [];

        /**
         * @var int    $accountId
         * @var string $entry
         */
        foreach ($spent as $accountId => $entry) {
            if (!isset($return[$accountId])) {
                $return[$accountId] = ['spent' => 0, 'earned' => 0];
            }

            $return[$accountId]['spent'] = $entry;
        }
        unset($entry);

        /**
         * @var int    $accountId
         * @var string $entry
         */
        foreach ($earned as $accountId => $entry) {
            if (!isset($return[$accountId])) {
                $return[$accountId] = ['spent' => 0, 'earned' => 0];
            }

            $return[$accountId]['earned'] = $entry;
        }


        return $return;

    }

    /**
     * @return array
     */
    private function getCategorySummary(): array
    {
        $spent  = $this->getSpentCategorySummary();
        $earned = $this->getEarnedCategorySummary();
        $return = [];

        /**
         * @var int    $categoryId
         * @var string $entry
         */
        foreach ($spent as $categoryId => $entry) {
            if (!isset($return[$categoryId])) {
                $return[$categoryId] = ['spent' => 0, 'earned' => 0];
            }

            $return[$categoryId]['spent'] = $entry;
        }
        unset($entry);

        /**
         * @var int    $categoryId
         * @var string $entry
         */
        foreach ($earned as $categoryId => $entry) {
            if (!isset($return[$categoryId])) {
                $return[$categoryId] = ['spent' => 0, 'earned' => 0];
            }

            $return[$categoryId]['earned'] = $entry;
        }

        return $return;
    }

    /**
     * @return array
     */
    private function getEarnedAccountSummary(): array
    {
        $transactions = $this->getIncome();
        $result       = [];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $accountId          = $transaction->account_id;
            $result[$accountId] = $result[$accountId] ?? '0';
            $result[$accountId] = bcadd($transaction->transaction_amount, $result[$accountId]);
        }

        return $result;
    }

    /**
     * @return array
     */
    private function getEarnedCategorySummary(): array
    {
        $transactions = $this->getIncome();
        $result       = [];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $jrnlCatId  = intval($transaction->transaction_journal_category_id);
            $transCatId = intval($transaction->transaction_category_id);
            $categoryId = max($jrnlCatId, $transCatId);

            $result[$categoryId] = $result[$categoryId] ?? '0';
            $result[$categoryId] = bcadd($transaction->transaction_amount, $result[$categoryId]);
        }

        return $result;
    }

    /**
     * @return Collection
     */
    private function getExpenses(): Collection
    {
        $collector = new JournalCollector(auth()->user());
        $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)
                  ->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->setCategories($this->categories)->getOpposingAccount()->disableFilter();

        $accountIds   = $this->accounts->pluck('id')->toArray();
        $transactions = $collector->getJournals();
        $transactions = self::filterExpenses($transactions, $accountIds);


        return $transactions;
    }

    /**
     * @return Collection
     */
    private function getIncome(): Collection
    {
        $collector = new JournalCollector(auth()->user());
        $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)
                  ->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
                  ->setCategories($this->categories)->getOpposingAccount();
        $accountIds   = $this->accounts->pluck('id')->toArray();
        $transactions = $collector->getJournals();
        $transactions = self::filterIncome($transactions, $accountIds);

        return $transactions;
    }

    /**
     * @return array
     */
    private function getSpentAccountSummary(): array
    {
        $transactions = $this->getExpenses();
        $result       = [];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $accountId          = $transaction->account_id;
            $result[$accountId] = $result[$accountId] ?? '0';
            $result[$accountId] = bcadd($transaction->transaction_amount, $result[$accountId]);
        }

        return $result;
    }

    /**
     * @return array
     */
    private function getSpentCategorySummary(): array
    {
        $transactions = $this->getExpenses();
        $result       = [];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $jrnlCatId  = intval($transaction->transaction_journal_category_id);
            $transCatId = intval($transaction->transaction_category_id);
            $categoryId = max($jrnlCatId, $transCatId);

            $result[$categoryId] = $result[$categoryId] ?? '0';
            $result[$categoryId] = bcadd($transaction->transaction_amount, $result[$categoryId]);
        }

        return $result;


    }
}