<?php
/**
 * MonthReportGenerator.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
/** @noinspection MultipleReturnStatementsInspection */
/** @noinspection PhpUndefinedMethodInspection */
declare(strict_types=1);

namespace FireflyIII\Generator\Report\Category;

use Carbon\Carbon;
use FireflyIII\Generator\Report\ReportGeneratorInterface;
use FireflyIII\Generator\Report\Support;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;
use Log;
use Throwable;

/**
 * Class MonthReportGenerator.
 * TODO include info about tags.
 *
 * @codeCoverageIgnore
 */
class MonthReportGenerator implements ReportGeneratorInterface
{
    /** @var Collection The included accounts */
    private $accounts;
    /** @var Collection The included categories */
    private $categories;
    /** @var Carbon The end date */
    private $end;
    /** @var array The expenses */
    private $expenses;
    /** @var array The income in the report. */
    private $income;
    /** @var Carbon The start date. */
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
     * Generates the report.
     *
     * @return string
     */
    public function generate(): string
    {
        $accountIds  = implode(',', $this->accounts->pluck('id')->toArray());
        $categoryIds = implode(',', $this->categories->pluck('id')->toArray());
        $reportType  = 'category';

        // render!
        try {
            return view('reports.category.month', compact('accountIds', 'categoryIds', 'reportType',))
                ->with('start', $this->start)->with('end', $this->end)
                ->with('categories', $this->categories)
                ->with('accounts', $this->accounts)
                ->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Cannot render reports.category.month: %s', $e->getMessage()));
            $result = sprintf('Could not render report view: %s', $e->getMessage());
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
     * Empty budget setter.
     *
     * @param Collection $budgets
     *
     * @return ReportGeneratorInterface
     */
    public function setBudgets(Collection $budgets): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Set the categories involved in this report.
     *
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
     * Set the end date for this report.
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
     * Set the expenses involved in this report.
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
     * Set the start date for this report.
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
     * Unused tag setter.
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
     * Get the expenses for this report.
     *
     * @return array
     */
    protected function getExpenses(): array
    {
        if (count($this->expenses) > 0) {
            Log::debug('Return previous set of expenses.');

            return $this->expenses;
        }

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)
                  ->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->setCategories($this->categories)->withAccountInformation();

        $transactions   = $collector->getExtractedJournals();
        $this->expenses = $transactions;

        return $transactions;
    }

    /**
     * Get the income for this report.
     *
     * @return array
     */
    protected function getIncome(): array
    {
        if (count($this->income) > 0) {
            return $this->income;
        }

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)
                  ->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
                  ->setCategories($this->categories)->withAccountInformation();

        $transactions = $collector->getExtractedJournals();
        $this->income = $transactions;

        return $transactions;
    }
}
