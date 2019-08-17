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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Chart\MetaPieChartInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Tag;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\AugumentData;
use FireflyIII\Support\Http\Controllers\TransactionCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class TagReportController
 */
class TagReportController extends Controller
{
    use AugumentData, TransactionCalculation;
    /** @var GeneratorInterface Chart generation methods. */
    protected $generator;

    /**
     * TagReportController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app(GeneratorInterface::class);
    }


    /**
     * Generate expenses for tags grouped on account.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return JsonResponse
     */
    public function accountExpense(Collection $accounts, Collection $tags, Carbon $start, Carbon $end, string $others): JsonResponse
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setTags($tags);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(1 === (int)$others);
        $chartData = $helper->generate('expense', 'account');
        $data      = $this->generator->pieChart($chartData);

        return response()->json($data);
    }


    /**
     * Generate income for tag grouped by account.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return JsonResponse
     */
    public function accountIncome(Collection $accounts, Collection $tags, Carbon $start, Carbon $end, string $others): JsonResponse
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setTags($tags);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(1 === (int)$others);
        $chartData = $helper->generate('income', 'account');
        $data      = $this->generator->pieChart($chartData);

        return response()->json($data);
    }


    /**
     * Generate expense for tag grouped on budget.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     */
    public function budgetExpense(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): JsonResponse
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

        return response()->json($data);
    }


    /**
     * Generate expense for tag grouped on category.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     */
    public function categoryExpense(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): JsonResponse
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

        return response()->json($data);
    }


    /**
     * Generate main tag overview chart.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     *
     *
     */
    public function mainChart(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.category.report.main');
        $cache->addProperty($accounts);
        $cache->addProperty($tags);
        $cache->addProperty($start);
        $cache->addProperty($end);
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        $format       = app('navigation')->preferredCarbonLocalizedFormat($start, $end);
        $function     = app('navigation')->preferredEndOfPeriod($start, $end);
        $chartData    = [];
        $currentStart = clone $start;

        // prep chart data:
        foreach ($tags as $tag) {
            $chartData[$tag->id . '-in']  = [
                'label'   => $tag->tag . ' (' . strtolower((string)trans('firefly.income')) . ')',
                'type'    => 'bar',
                'yAxisID' => 'y-axis-0',
                'entries' => [],
            ];
            $chartData[$tag->id . '-out'] = [
                'label'   => $tag->tag . ' (' . strtolower((string)trans('firefly.expenses')) . ')',
                'type'    => 'bar',
                'yAxisID' => 'y-axis-0',
                'entries' => [],
            ];
            // total in, total out:
            $chartData[$tag->id . '-total-in']  = [
                'label'   => $tag->tag . ' (' . strtolower((string)trans('firefly.sum_of_income')) . ')',
                'type'    => 'line',
                'fill'    => false,
                'yAxisID' => 'y-axis-1',
                'entries' => [],
            ];
            $chartData[$tag->id . '-total-out'] = [
                'label'   => $tag->tag . ' (' . strtolower((string)trans('firefly.sum_of_expenses')) . ')',
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
            $expenses   = $this->groupByTag($this->getExpensesForTags($accounts, $tags, $currentStart, $currentEnd));
            $income     = $this->groupByTag($this->getIncomeForTags($accounts, $tags, $currentStart, $currentEnd));
            $label      = $currentStart->formatLocalized($format);

            /** @var Tag $tag */
            foreach ($tags as $tag) {
                $labelIn        = $tag->id . '-in';
                $labelOut       = $tag->id . '-out';
                $labelSumIn     = $tag->id . '-total-in';
                $labelSumOut    = $tag->id . '-total-out';
                $currentIncome  = bcmul($income[$tag->id] ?? '0','-1');
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
            /** @var Carbon $currentStart */
            $currentStart = clone $currentEnd;
            $currentStart->addDay();
            $currentStart->startOfDay();

        }
        // remove all empty entries to prevent cluttering:
        $newSet = [];
        foreach ($chartData as $key => $entry) {
            if (0 === !array_sum($entry['entries'])) {
                $newSet[$key] = $chartData[$key]; // @codeCoverageIgnore
            }
        }
        if (0 === count($newSet)) {
            $newSet = $chartData; // @codeCoverageIgnore
        }
        $data = $this->generator->multiSet($newSet);
        $cache->store($data);

        return response()->json($data);
    }


    /**
     * Show expense grouped by expense account.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return JsonResponse
     */
    public function tagExpense(Collection $accounts, Collection $tags, Carbon $start, Carbon $end, string $others): JsonResponse
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setTags($tags);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(1 === (int)$others);
        $chartData = $helper->generate('expense', 'tag');
        $data      = $this->generator->pieChart($chartData);

        return response()->json($data);
    }


    /**
     * Show income grouped by tag.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return JsonResponse
     */
    public function tagIncome(Collection $accounts, Collection $tags, Carbon $start, Carbon $end, string $others): JsonResponse
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setTags($tags);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(1 === (int)$others);
        $chartData = $helper->generate('income', 'tag');
        $data      = $this->generator->pieChart($chartData);

        return response()->json($data);
    }
}
