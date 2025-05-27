<?php

/**
 * MonthReportGenerator.php
 * Copyright (c) 2019 james@firefly-iii.org
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
declare(strict_types=1);

namespace FireflyIII\Generator\Report\Account;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Generator\Report\ReportGeneratorInterface;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Class MonthReportGenerator.
 */
class MonthReportGenerator implements ReportGeneratorInterface
{
    private Collection $accounts;
    private Carbon     $end;
    private Collection $expense;
    private Carbon     $start;

    /**
     * Generate the report.
     *
     * @throws FireflyException
     */
    public function generate(): string
    {
        $accountIds      = implode(',', $this->accounts->pluck('id')->toArray());
        $doubleIds       = implode(',', $this->expense->pluck('id')->toArray());
        $reportType      = 'account';
        $preferredPeriod = $this->preferredPeriod();

        try {
            $result = view('reports.double.report', compact('accountIds', 'reportType', 'doubleIds', 'preferredPeriod'))
                ->with('start', $this->start)->with('end', $this->end)
                ->with('doubles', $this->expense)
                ->render()
            ;
        } catch (Throwable $e) {
            app('log')->error(sprintf('Cannot render reports.double.report: %s', $e->getMessage()));
            app('log')->error($e->getTraceAsString());
            $result = sprintf('Could not render report view: %s', $e->getMessage());

            throw new FireflyException($result, 0, $e);
        }

        return $result;
    }

    /**
     * Return the preferred period.
     */
    protected function preferredPeriod(): string
    {
        return 'day';
    }

    /**
     * Set accounts.
     */
    public function setAccounts(Collection $accounts): ReportGeneratorInterface
    {
        $this->accounts = $accounts;

        return $this;
    }

    /**
     * Set budgets.
     */
    public function setBudgets(Collection $budgets): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Set categories.
     */
    public function setCategories(Collection $categories): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Set end date.
     */
    public function setEndDate(Carbon $date): ReportGeneratorInterface
    {
        $this->end = $date;

        return $this;
    }

    /**
     * Set expense collection.
     */
    public function setExpense(Collection $expense): ReportGeneratorInterface
    {
        $this->expense = $expense;

        return $this;
    }

    /**
     * Set start date.
     */
    public function setStartDate(Carbon $date): ReportGeneratorInterface
    {
        $this->start = $date;

        return $this;
    }

    /**
     * Set collection of tags.
     */
    public function setTags(Collection $tags): ReportGeneratorInterface
    {
        return $this;
    }
}
