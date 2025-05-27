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

namespace FireflyIII\Generator\Report\Category;

use Carbon\Carbon;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Generator\Report\ReportGeneratorInterface;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Class MonthReportGenerator.
 * TODO include info about tags
 */
class MonthReportGenerator implements ReportGeneratorInterface
{
    private Collection $accounts;
    private Collection $categories;
    private Carbon     $end;
    private array      $expenses;
    private array      $income;
    private Carbon     $start;

    /**
     * MonthReportGenerator constructor.
     */
    public function __construct()
    {
        $this->income   = [];
        $this->expenses = [];
    }

    /**
     * Generates the report.
     *
     * @throws FireflyException
     */
    public function generate(): string
    {
        $accountIds  = implode(',', $this->accounts->pluck('id')->toArray());
        $categoryIds = implode(',', $this->categories->pluck('id')->toArray());
        $reportType  = 'category';

        // render!
        try {
            return view('reports.category.month', compact('accountIds', 'categoryIds', 'reportType'))
                ->with('start', $this->start)->with('end', $this->end)
                ->with('categories', $this->categories)
                ->with('accounts', $this->accounts)
                ->render()
            ;
        } catch (Throwable $e) {
            app('log')->error(sprintf('Cannot render reports.category.month: %s', $e->getMessage()));
            app('log')->error($e->getTraceAsString());
            $result = sprintf('Could not render report view: %s', $e->getMessage());

            throw new FireflyException($result, 0, $e);
        }
    }

    /**
     * Empty budget setter.
     */
    public function setBudgets(Collection $budgets): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Set the end date for this report.
     */
    public function setEndDate(Carbon $date): ReportGeneratorInterface
    {
        $this->end = $date;

        return $this;
    }

    /**
     * Set the expenses involved in this report.
     */
    public function setExpense(Collection $expense): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Set the start date for this report.
     */
    public function setStartDate(Carbon $date): ReportGeneratorInterface
    {
        $this->start = $date;

        return $this;
    }

    /**
     * Unused tag setter.
     */
    public function setTags(Collection $tags): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Get the expenses for this report.
     */
    protected function getExpenses(): array
    {
        if (0 !== count($this->expenses)) {
            app('log')->debug('Return previous set of expenses.');

            return $this->expenses;
        }

        /** @var GroupCollectorInterface $collector */
        $collector      = app(GroupCollectorInterface::class);
        $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)
            ->setTypes([TransactionTypeEnum::WITHDRAWAL->value, TransactionTypeEnum::TRANSFER->value])
            ->setCategories($this->categories)->withAccountInformation()
        ;

        $transactions   = $collector->getExtractedJournals();
        $this->expenses = $transactions;

        return $transactions;
    }

    /**
     * Set the categories involved in this report.
     */
    public function setCategories(Collection $categories): ReportGeneratorInterface
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * Set the involved accounts.
     */
    public function setAccounts(Collection $accounts): ReportGeneratorInterface
    {
        $this->accounts = $accounts;

        return $this;
    }

    /**
     * Get the income for this report.
     */
    protected function getIncome(): array
    {
        if (0 !== count($this->income)) {
            return $this->income;
        }

        /** @var GroupCollectorInterface $collector */
        $collector    = app(GroupCollectorInterface::class);

        $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)
            ->setTypes([TransactionTypeEnum::DEPOSIT->value, TransactionTypeEnum::TRANSFER->value])
            ->setCategories($this->categories)->withAccountInformation()
        ;

        $transactions = $collector->getExtractedJournals();
        $this->income = $transactions;

        return $transactions;
    }
}
