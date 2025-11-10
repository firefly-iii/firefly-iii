<?php

/**
 * YearReportGenerator.php
 * Copyright (c) 2025 james@firefly-iii.org
 * 
 * Contributed by: Mukesh Kesharwani
 * Date: November 10, 2025
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

namespace FireflyIII\Generator\Report\AssetLiability;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Generator\Report\ReportGeneratorInterface;
use Illuminate\Support\Collection;
use Throwable;
use Illuminate\Support\Facades\Log;

/**
 * Class YearReportGenerator.
 * 
 * Generates a comprehensive Asset & Liability report for a year period.
 * Shows account balances at start and end of year with changes.
 */
class YearReportGenerator implements ReportGeneratorInterface
{
    /** @var Collection The accounts involved in the report. */
    private $accounts;

    /** @var Carbon The end date. */
    private $end;

    /** @var Carbon The start date. */
    private $start;

    /**
     * Generates the Asset & Liability report for a year period.
     *
     * @throws FireflyException
     */
    public function generate(): string
    {
        $accountIds = implode(',', $this->accounts->pluck('id')->toArray());
        $reportType = 'assetliability';

        try {
            return view('reports.assetliability.year', compact('accountIds', 'reportType'))
                ->with('start', $this->start)
                ->with('end', $this->end)
                ->with('accounts', $this->accounts)
                ->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Cannot render reports.assetliability.year: %s', $e->getMessage()));
            Log::error($e->getTraceAsString());
            $result = 'Could not render asset & liability report view.';

            throw new FireflyException($result, 0, $e);
        }
    }

    /**
     * Sets the accounts involved in the report.
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
     * Unused category setter.
     */
    public function setCategories(Collection $categories): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Set the end date of the report.
     */
    public function setEndDate(Carbon $date): ReportGeneratorInterface
    {
        $this->end = $date;

        return $this;
    }

    /**
     * Set the expenses used in this report.
     */
    public function setExpense(Collection $expense): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Set the start date of this report.
     */
    public function setStartDate(Carbon $date): ReportGeneratorInterface
    {
        $this->start = $date;

        return $this;
    }

    /**
     * Set the tags used in this report.
     */
    public function setTags(Collection $tags): ReportGeneratorInterface
    {
        return $this;
    }
}

