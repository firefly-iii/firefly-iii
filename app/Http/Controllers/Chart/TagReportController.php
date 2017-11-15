<?php
/**
 * TagReportController.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Chart\MetaPieChartInterface;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\NegativeAmountFilter;
use FireflyIII\Helpers\Filter\OpposingAccountFilter;
use FireflyIII\Helpers\Filter\PositiveAmountFilter;
use FireflyIII\Helpers\Filter\TransferFilter;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Navigation;
use Response;

class TagReportController extends Controller
{
    /** @var GeneratorInterface */
    protected $generator;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app(GeneratorInterface::class);
    }

    /**
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function accountExpense(Collection $accounts, Collection $tags, Carbon $start, Carbon $end, string $others)
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setTags($tags);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(1 === intval($others));
        $chartData = $helper->generate('expense', 'account');
        $data      = $this->generator->pieChart($chartData);

        return Response::json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function accountIncome(Collection $accounts, Collection $tags, Carbon $start, Carbon $end, string $others)
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setTags($tags);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(1 === intval($others));
        $chartData = $helper->generate('income', 'account');
        $data      = $this->generator->pieChart($chartData);

        return Response::json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function budgetExpense(Collection $accounts, Collection $tags, Carbon $start, Carbon $end)
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setTags($tags);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(false);
        $chartData = $helper->generate('expense', 'budget');
        $data      = $this->generator->pieChart($chartData);

        return Response::json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categoryExpense(Collection $accounts, Collection $tags, Carbon $start, Carbon $end)
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setTags($tags);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(false);
        $chartData = $helper->generate('expense', 'category');
        $data      = $this->generator->pieChart($chartData);

        return Response::json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function mainChart(Collection $accounts, Collection $tags, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.category.report.main');
        $cache->addProperty($accounts);
        $cache->addProperty($tags);
        $cache->addProperty($start);
        $cache->addProperty($end);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $format       = Navigation::preferredCarbonLocalizedFormat($start, $end);
        $function     = Navigation::preferredEndOfPeriod($start, $end);
        $chartData    = [];
        $currentStart = clone $start;

        // prep chart data:
        foreach ($tags as $tag) {
            $chartData[$tag->id . '-in']  = [
                'label'   => $tag->tag . ' (' . strtolower(strval(trans('firefly.income'))) . ')',
                'type'    => 'bar',
                'yAxisID' => 'y-axis-0',
                'entries' => [],
            ];
            $chartData[$tag->id . '-out'] = [
                'label'   => $tag->tag . ' (' . strtolower(strval(trans('firefly.expenses'))) . ')',
                'type'    => 'bar',
                'yAxisID' => 'y-axis-0',
                'entries' => [],
            ];
            // total in, total out:
            $chartData[$tag->id . '-total-in']  = [
                'label'   => $tag->tag . ' (' . strtolower(strval(trans('firefly.sum_of_income'))) . ')',
                'type'    => 'line',
                'fill'    => false,
                'yAxisID' => 'y-axis-1',
                'entries' => [],
            ];
            $chartData[$tag->id . '-total-out'] = [
                'label'   => $tag->tag . ' (' . strtolower(strval(trans('firefly.sum_of_expenses'))) . ')',
                'type'    => 'line',
                'fill'    => false,
                'yAxisID' => 'y-axis-1',
                'entries' => [],
            ];
        }
        $sumOfIncome  = [];
        $sumOfExpense = [];

        while ($currentStart < $end) {
            $currentEnd = clone $currentStart;
            $currentEnd = $currentEnd->$function();
            $expenses   = $this->groupByTag($this->getExpenses($accounts, $tags, $currentStart, $currentEnd));
            $income     = $this->groupByTag($this->getIncome($accounts, $tags, $currentStart, $currentEnd));
            $label      = $currentStart->formatLocalized($format);

            /** @var Tag $tag */
            foreach ($tags as $tag) {
                $labelIn        = $tag->id . '-in';
                $labelOut       = $tag->id . '-out';
                $labelSumIn     = $tag->id . '-total-in';
                $labelSumOut    = $tag->id . '-total-out';
                $currentIncome  = $income[$tag->id] ?? '0';
                $currentExpense = $expenses[$tag->id] ?? '0';

                // add to sum:
                $sumOfIncome[$tag->id]  = $sumOfIncome[$tag->id] ?? '0';
                $sumOfExpense[$tag->id] = $sumOfExpense[$tag->id] ?? '0';
                $sumOfIncome[$tag->id]  = bcadd($sumOfIncome[$tag->id], $currentIncome);
                $sumOfExpense[$tag->id] = bcadd($sumOfExpense[$tag->id], $currentExpense);

                // add to chart:
                $chartData[$labelIn]['entries'][$label]     = $currentIncome;
                $chartData[$labelOut]['entries'][$label]    = $currentExpense;
                $chartData[$labelSumIn]['entries'][$label]  = $sumOfIncome[$tag->id];
                $chartData[$labelSumOut]['entries'][$label] = $sumOfExpense[$tag->id];
            }
            $currentStart = clone $currentEnd;
            $currentStart->addDay();
        }
        // remove all empty entries to prevent cluttering:
        $newSet = [];
        foreach ($chartData as $key => $entry) {
            if (0 === !array_sum($entry['entries'])) {
                $newSet[$key] = $chartData[$key];
            }
        }
        if (0 === count($newSet)) {
            $newSet = $chartData; // @codeCoverageIgnore
        }
        $data = $this->generator->multiSet($newSet);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tagExpense(Collection $accounts, Collection $tags, Carbon $start, Carbon $end, string $others)
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setTags($tags);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(1 === intval($others));
        $chartData = $helper->generate('expense', 'tag');
        $data      = $this->generator->pieChart($chartData);

        return Response::json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tagIncome(Collection $accounts, Collection $tags, Carbon $start, Carbon $end, string $others)
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setTags($tags);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(1 === intval($others));
        $chartData = $helper->generate('income', 'tag');
        $data      = $this->generator->pieChart($chartData);

        return Response::json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    private function getExpenses(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): Collection
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->setTags($tags)->withOpposingAccount();
        $collector->removeFilter(TransferFilter::class);

        $collector->addFilter(OpposingAccountFilter::class);
        $collector->addFilter(PositiveAmountFilter::class);

        $transactions = $collector->getJournals();

        return $transactions;
    }

    /**
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    private function getIncome(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): Collection
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
                  ->setTags($tags)->withOpposingAccount();

        $collector->addFilter(OpposingAccountFilter::class);
        $collector->addFilter(NegativeAmountFilter::class);

        $transactions = $collector->getJournals();

        return $transactions;
    }

    /**
     * @param Collection $set
     *
     * @return array
     */
    private function groupByTag(Collection $set): array
    {
        // group by category ID:
        $grouped = [];
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $journal     = $transaction->transactionJournal;
            $journalTags = $journal->tags;
            /** @var Tag $journalTag */
            foreach ($journalTags as $journalTag) {
                $journalTagId           = $journalTag->id;
                $grouped[$journalTagId] = $grouped[$journalTagId] ?? '0';
                $grouped[$journalTagId] = bcadd($transaction->transaction_amount, $grouped[$journalTagId]);
            }
        }

        return $grouped;
    }
}
