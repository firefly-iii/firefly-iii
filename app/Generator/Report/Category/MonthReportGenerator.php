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
use Crypt;
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
        $categoryIds     = join(',', $this->categories->pluck('id')->toArray());
        $reportType      = 'category';
        $expenses        = $this->getExpenses();
        $income          = $this->getIncome();
        $accountSummary  = $this->getObjectSummary($this->summarizeByAccount($expenses), $this->summarizeByAccount($income));
        $categorySummary = $this->getObjectSummary($this->summarizeByCategory($expenses), $this->summarizeByCategory($income));
        $averageExpenses = $this->getAverages($expenses, SORT_ASC);
        $averageIncome   = $this->getAverages($income, SORT_DESC);
        $topExpenses     = $this->getTopExpenses();
        $topIncome       = $this->getTopIncome();


        // render!
        return view(
            'reports.category.month',
            compact(
                'accountIds', 'categoryIds', 'topIncome', 'reportType', 'accountSummary', 'categorySummary', 'averageExpenses', 'averageIncome', 'topExpenses'
            )
        )
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

        $collector = new JournalCollector(auth()->user());
        $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)
                  ->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->setCategories($this->categories)->withOpposingAccount()->disableFilter();

        $accountIds     = $this->accounts->pluck('id')->toArray();
        $transactions   = $collector->getJournals();
        $transactions   = self::filterExpenses($transactions, $accountIds);
        $this->expenses = $transactions;

        return $transactions;
    }

    /**
     * @return Collection
     */
    private function getIncome(): Collection
    {
        if ($this->income->count() > 0) {
            return $this->income;
        }

        $collector = new JournalCollector(auth()->user());
        $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)
                  ->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
                  ->setCategories($this->categories)->withOpposingAccount();
        $accountIds   = $this->accounts->pluck('id')->toArray();
        $transactions = $collector->getJournals();
        $transactions = self::filterIncome($transactions, $accountIds);
        $this->income = $transactions;

        return $transactions;
    }

    /**
     * @param array $spent
     * @param array $earned
     *
     * @return array
     */
    private function getObjectSummary(array $spent, array $earned): array
    {
        $return = [];

        /**
         * @var int    $accountId
         * @var string $entry
         */
        foreach ($spent as $objectId => $entry) {
            if (!isset($return[$objectId])) {
                $return[$objectId] = ['spent' => 0, 'earned' => 0];
            }

            $return[$objectId]['spent'] = $entry;
        }
        unset($entry);

        /**
         * @var int    $accountId
         * @var string $entry
         */
        foreach ($earned as $objectId => $entry) {
            if (!isset($return[$objectId])) {
                $return[$objectId] = ['spent' => 0, 'earned' => 0];
            }

            $return[$objectId]['earned'] = $entry;
        }


        return $return;
    }


    /**
     * @return Collection
     */
    private function getTopExpenses(): Collection
    {
        $transactions = $this->getExpenses()->sortBy('transaction_amount');

        $transactions = $transactions->each(
            function (Transaction $transaction) {
                if (intval($transaction->opposing_account_encrypted) === 1) {
                    $transaction->opposing_account_name = Crypt::decrypt($transaction->opposing_account_name);
                }
            }
        );

        return $transactions;
    }

    /**
     * @return Collection
     */
    private function getTopIncome(): Collection
    {
        $transactions = $this->getIncome()->sortByDesc('transaction_amount');

        $transactions = $transactions->each(
            function (Transaction $transaction) {
                if (intval($transaction->opposing_account_encrypted) === 1) {
                    $transaction->opposing_account_name = Crypt::decrypt($transaction->opposing_account_name);
                }
            }
        );

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
    private function summarizeByCategory(Collection $collection): array
    {
        $result = [];
        /** @var Transaction $transaction */
        foreach ($collection as $transaction) {
            $jrnlCatId           = intval($transaction->transaction_journal_category_id);
            $transCatId          = intval($transaction->transaction_category_id);
            $categoryId          = max($jrnlCatId, $transCatId);
            $result[$categoryId] = $result[$categoryId] ?? '0';
            $result[$categoryId] = bcadd($transaction->transaction_amount, $result[$categoryId]);
        }

        return $result;
    }
}