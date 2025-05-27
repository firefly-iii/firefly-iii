<?php

/**
 * MultiYearReportGenerator.php
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

namespace FireflyIII\Generator\Report\Standard;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Generator\Report\ReportGeneratorInterface;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Class MonthReportGenerator.
 */
class MultiYearReportGenerator implements ReportGeneratorInterface
{
    /** @var Collection The accounts involved. */
    private $accounts;

    /** @var Carbon The end date. */
    private $end;

    /** @var Carbon The start date. */
    private $start;

    /**
     * Generates the report.
     *
     * @throws FireflyException
     */
    public function generate(): string
    {
        // and some id's, joined:
        $accountIds = implode(',', $this->accounts->pluck('id')->toArray());
        $reportType = 'default';

        try {
            return view(
                'reports.default.multi-year',
                compact('accountIds', 'reportType')
            )->with('start', $this->start)->with('end', $this->end)->render();
        } catch (Throwable $e) {
            app('log')->error(sprintf('Cannot render reports.default.multi-year: %s', $e->getMessage()));
            app('log')->error($e->getTraceAsString());
            $result = sprintf('Could not render report view: %s', $e->getMessage());

            throw new FireflyException($result, 0, $e);
        }
    }

    /**
     * Sets the accounts used in the report.
     */
    public function setAccounts(Collection $accounts): ReportGeneratorInterface
    {
        $this->accounts = $accounts;

        return $this;
    }

    /**
     * Sets the budgets used in the report.
     */
    public function setBudgets(Collection $budgets): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Sets the categories used in the report.
     */
    public function setCategories(Collection $categories): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Sets the end date used in the report.
     */
    public function setEndDate(Carbon $date): ReportGeneratorInterface
    {
        $this->end = $date;

        return $this;
    }

    /**
     * Unused setter for expenses.
     */
    public function setExpense(Collection $expense): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Set the start date of the report.
     */
    public function setStartDate(Carbon $date): ReportGeneratorInterface
    {
        $this->start = $date;

        return $this;
    }

    /**
     * Set the tags for the report.
     */
    public function setTags(Collection $tags): ReportGeneratorInterface
    {
        return $this;
    }
}
