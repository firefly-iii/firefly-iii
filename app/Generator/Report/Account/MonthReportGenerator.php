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

/**
 * Class MonthReportGenerator.
 */
class MonthReportGenerator implements ReportGeneratorInterface
{
    /** @var Collection */
    private $accounts;
    /** @var Carbon */
    private $end;
    /** @var Collection */
    private $expense;
    /** @var Carbon */
    private $start;

    /**
     * @return string
     *
     * @throws \Throwable
     */
    public function generate(): string
    {
        $accountIds      = join(',', $this->accounts->pluck('id')->toArray());
        $expenseIds      = join(',', $this->expense->pluck('id')->toArray());
        $reportType      = 'account';
        $preferredPeriod = $this->preferredPeriod();

        return view(
            'reports.account.report',
            compact('accountIds', 'reportType', 'expenseIds', 'preferredPeriod')
        )->with('start', $this->start)->with('end', $this->end)->render();
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
     * @param Collection $tags
     *
     * @return ReportGeneratorInterface
     */
    public function setTags(Collection $tags): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * @return string
     */
    protected function preferredPeriod(): string
    {
        return 'day';
    }
}
