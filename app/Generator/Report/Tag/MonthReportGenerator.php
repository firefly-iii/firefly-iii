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

/** @noinspection MultipleReturnStatementsInspection */
/** @noinspection PhpUndefinedMethodInspection */
declare(strict_types=1);

namespace FireflyIII\Generator\Report\Tag;

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
 *
 * @codeCoverageIgnore
 */
class MonthReportGenerator extends Support implements ReportGeneratorInterface
{
    /** @var Collection The accounts involved */
    private $accounts;
    /** @var Carbon The end date */
    private $end;
    /** @var array The expenses involved */
    private $expenses;
    /** @var array The income involved */
    private $income;
    /** @var Carbon The start date */
    private $start;
    /** @var Collection The tags involved. */
    private $tags;

    /**
     * MonthReportGenerator constructor.
     */
    public function __construct()
    {
        $this->expenses = new Collection;
        $this->income   = new Collection;
        $this->tags     = new Collection;
    }

    /**
     * Generate the report.
     *
     * @return string
     */
    public function generate(): string
    {
        $accountIds      = implode(',', $this->accounts->pluck('id')->toArray());
        $tagTags         = implode(',', $this->tags->pluck('tag')->toArray());
        $tagIds          = implode(',', $this->tags->pluck('id')->toArray());
        $reportType      = 'tag';
        $expenses        = $this->getExpenses();
        $income          = $this->getIncome();
        $accountSummary  = $this->getObjectSummary($this->summarizeByAssetAccount($expenses), $this->summarizeByAssetAccount($income));
        $tagSummary      = $this->getObjectSummary($this->summarizeByTag($expenses), $this->summarizeByTag($income));
        $averageExpenses = $this->getAverages($expenses, SORT_ASC);
        $averageIncome   = $this->getAverages($income, SORT_DESC);
        $topExpenses     = $this->getTopExpenses();
        $topIncome       = $this->getTopIncome();

        // render!
        try {
            $result = view(
                'reports.tag.month', compact(
                                       'accountIds', 'tagTags', 'reportType', 'accountSummary', 'tagSummary', 'averageExpenses', 'averageIncome', 'topIncome',
                                       'topExpenses', 'tagIds'
                                   )
            )->with('start', $this->start)->with('end', $this->end)->with('tags', $this->tags)->with('accounts', $this->accounts)->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Cannot render reports.tag.month: %s', $e->getMessage()));
            $result = 'Could not render report view.';
        }

        return $result;
    }

    /**
     * Set the accounts.
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
     * Unused budget setter.
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
     * Unused category setter.
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
     * Set the end date of the report.
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
     * Set the expenses in this report.
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
     * Set the start date.
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
     * Set the tags used in this report.
     *
     * @param Collection $tags
     *
     * @return ReportGeneratorInterface
     */
    public function setTags(Collection $tags): ReportGeneratorInterface
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Get expense collection for report.
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
                  ->setTags($this->tags)->withAccountInformation();

        $journals       = $collector->getExtractedJournals();
        $this->expenses = $journals;

        return $journals;
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
                  ->setTags($this->tags)->withAccountInformation();

        $journals     = $collector->getExtractedJournals();
        $this->income = $journals;

        return $journals;
    }

    /**
     * Summarize by tag.
     *
     * @param array $array
     *
     * @return array
     */
    protected function summarizeByTag(array $array): array
    {
        $tagIds = array_map('\intval', $this->tags->pluck('id')->toArray());
        $result = [];
        /** @var array $journal */
        foreach ($array as $journal) {
            /**
             * @var int $id
             * @var array $tag
             */
            foreach ($journal['tags'] as $id => $tag) {
                if (in_array($id, $tagIds, true)) {
                    $result[$id] = $result[$id] ?? '0';
                    $result[$id] = bcadd($journal['amount'], $result[$id]);
                }
            }
        }

        return $result;
    }
}
