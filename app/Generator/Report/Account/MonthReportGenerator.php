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
declare(strict_types=1);

namespace FireflyIII\Generator\Report\Account;

use Carbon\Carbon;
use FireflyIII\Generator\Report\ReportGeneratorInterface;
use Illuminate\Support\Collection;
use Log;
use Throwable;

/**
 * Class MonthReportGenerator.
 *
 * @codeCoverageIgnore
 */
class MonthReportGenerator implements ReportGeneratorInterface
{
    /** @var Collection The accounts involved in the report. */
    private $accounts;
    /** @var Carbon The end date */
    private $end;
    /** @var Collection The expense accounts. */
    private $expense;
    /** @var Carbon The start date. */
    private $start;

    /**
     * Generate the report.
     *
     * @return string
     */
    public function generate(): string
    {
        $accountIds      = implode(',', $this->accounts->pluck('id')->toArray());
        $expenseIds      = implode(',', $this->expense->pluck('id')->toArray());
        $reportType      = 'account';
        $preferredPeriod = $this->preferredPeriod();
        try {
            $result = view(
                'reports.double.report', compact('accountIds', 'reportType', 'expenseIds', 'preferredPeriod')
            )->with('start', $this->start)->with('end', $this->end)->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Cannot render reports.double.report: %s', $e->getMessage()));
            $result = sprintf('Could not render report view: %s', $e->getMessage());
        }

        return $result;
    }

    /**
     * Set accounts.
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
     * Set budgets.
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
     * Set categories.
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
     * Set end date.
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
     * Set expense collection.
     *
     * @param Collection $expense
     *
     * @return ReportGeneratorInterface
     */
    public function setExpense(Collection $expense): ReportGeneratorInterface
    {
        $this->expense = $expense;

        return $this;
    }

    /**
     * Set start date.
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
     * Set collection of tags.
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
     * Return the preferred period.
     *
     * @return string
     */
    protected function preferredPeriod(): string
    {
        return 'day';
    }
}
