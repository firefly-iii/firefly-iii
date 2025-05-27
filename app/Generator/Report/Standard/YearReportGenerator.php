<?php

/**
 * YearReportGenerator.php
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
class YearReportGenerator implements ReportGeneratorInterface
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
            $result = view(
                'reports.default.year',
                compact('accountIds', 'reportType')
            )->with('start', $this->start)->with('end', $this->end)->render();
        } catch (Throwable $e) {
            app('log')->error(sprintf('Cannot render reports.account.report: %s', $e->getMessage()));
            app('log')->error($e->getTraceAsString());
            $result = 'Could not render report view.';

            throw new FireflyException($result, 0, $e);
        }

        return $result;
    }

    /**
     * Set the accounts.
     */
    public function setAccounts(Collection $accounts): ReportGeneratorInterface
    {
        $this->accounts = $accounts;

        return $this;
    }

    /**
     * Unused budget setter.
     */
    public function setBudgets(Collection $budgets): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Unused categories setter.
     */
    public function setCategories(Collection $categories): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Set the end date.
     */
    public function setEndDate(Carbon $date): ReportGeneratorInterface
    {
        $this->end = $date;

        return $this;
    }

    /**
     * Set the expenses used.
     */
    public function setExpense(Collection $expense): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Set the start date.
     */
    public function setStartDate(Carbon $date): ReportGeneratorInterface
    {
        $this->start = $date;

        return $this;
    }

    /**
     * Unused tags setter.
     */
    public function setTags(Collection $tags): ReportGeneratorInterface
    {
        return $this;
    }
}
