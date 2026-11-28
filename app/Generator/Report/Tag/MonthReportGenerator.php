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

namespace FireflyIII\Generator\Report\Tag;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Generator\Report\ReportGeneratorInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;
use Override;

/**
 * Class MonthReportGenerator.
 */
class MonthReportGenerator implements ReportGeneratorInterface
{
    private Collection $accounts;
    private Carbon $end;
    private Carbon $start;
    private Collection $tags;

    /**
     * MonthReportGenerator constructor.
     */
    public function __construct()
    {
        $this->tags     = new Collection();
        $this->accounts = new Collection();
    }

    /**
     * Generate the report.
     *
     * @throws FireflyException
     */
    #[Override]
    public function generate(): string
    {
        $accountIds = implode(',', $this->accounts->pluck('id')->toArray());
        $tagIds     = implode(',', $this->tags->pluck('id')->toArray());
        $reportType = 'tag';

        // render!
        try {
            $result = view('reports.tag.month', [
                'accountIds' => $accountIds,
                'reportType' => $reportType,
                'tagIds'     => $tagIds,
                'start'      => $this->start,
                'end'        => $this->end,
                'tags'       => $this->tags,
                'accounts'   => $this->accounts,
            ])->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Cannot render reports.tag.month: %s', $e->getMessage()));
            Log::error($e->getTraceAsString());
            $result = sprintf('Could not render report view: %s', $e->getMessage());

            throw new FireflyException($result, 0, $e);
        }

        return $result;
    }

    /**
     * Set the accounts.
     */
    #[Override]
    public function setAccounts(Collection $accounts): ReportGeneratorInterface
    {
        $this->accounts = $accounts;

        return $this;
    }

    /**
     * Unused budget setter.
     */
    #[Override]
    public function setBudgets(Collection $budgets): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Unused category setter.
     */
    #[Override]
    public function setCategories(Collection $categories): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Set the end date of the report.
     */
    #[Override]
    public function setEndDate(Carbon $date): ReportGeneratorInterface
    {
        $this->end = $date;

        return $this;
    }

    /**
     * Set the expenses in this report.
     */
    #[Override]
    public function setExpense(Collection $expense): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Set the start date.
     */
    #[Override]
    public function setStartDate(Carbon $date): ReportGeneratorInterface
    {
        $this->start = $date;

        return $this;
    }

    /**
     * Set the tags used in this report.
     */
    #[Override]
    public function setTags(Collection $tags): ReportGeneratorInterface
    {
        $this->tags = $tags;

        return $this;
    }
}
