<?php
/**
 * CategoryReportController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Navigation;
use Response;


/**
 * Separate controller because many helper functions are shared.
 *
 * Class CategoryReportController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class CategoryReportController extends Controller
{

    /** @var  GeneratorInterface */
    private $generator;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->generator = app(GeneratorInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function accountExpense(Collection $accounts, Collection $categories, Carbon $start, Carbon $end, string $others)
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts)->setCategories($categories)->setStart($start)->setEnd($end)->setCollectOtherObjects(intval($others) === 1);

        $chartData = $helper->generate('expense', 'account');
        $data      = $this->generator->pieChart($chartData);

        return Response::json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function accountIncome(Collection $accounts, Collection $categories, Carbon $start, Carbon $end, string $others)
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setCategories($categories);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(intval($others) === 1);
        $chartData = $helper->generate('income', 'account');
        $data      = $this->generator->pieChart($chartData);

        return Response::json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categoryExpense(Collection $accounts, Collection $categories, Carbon $start, Carbon $end, string $others)
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setCategories($categories);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(intval($others) === 1);
        $chartData = $helper->generate('expense', 'category');
        $data      = $this->generator->pieChart($chartData);

        return Response::json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categoryIncome(Collection $accounts, Collection $categories, Carbon $start, Carbon $end, string $others)
    {

        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setCategories($categories);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(intval($others) === 1);
        $chartData = $helper->generate('income', 'category');
        $data      = $this->generator->pieChart($chartData);

        return Response::json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function mainChart(Collection $accounts, Collection $categories, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.category.report.main');
        $cache->addProperty($accounts);
        $cache->addProperty($categories);
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
        foreach ($categories as $category) {
            $chartData[$category->id . '-in']  = [
                'label'   => $category->name . ' (' . strtolower(strval(trans('firefly.income'))) . ')',
                'type'    => 'bar',
                'yAxisID' => 'y-axis-0',
                'entries' => [],
            ];
            $chartData[$category->id . '-out'] = [
                'label'   => $category->name . ' (' . strtolower(strval(trans('firefly.expenses'))) . ')',
                'type'    => 'bar',
                'yAxisID' => 'y-axis-0',
                'entries' => [],
            ];
            // total in, total out:
            $chartData[$category->id . '-total-in']  = [
                'label'   => $category->name . ' (' . strtolower(strval(trans('firefly.sum_of_income'))) . ')',
                'type'    => 'line',
                'fill'    => false,
                'yAxisID' => 'y-axis-1',
                'entries' => [],
            ];
            $chartData[$category->id . '-total-out'] = [
                'label'   => $category->name . ' (' . strtolower(strval(trans('firefly.sum_of_expenses'))) . ')',
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
            $expenses   = $this->groupByCategory($this->getExpenses($accounts, $categories, $currentStart, $currentEnd));
            $income     = $this->groupByCategory($this->getIncome($accounts, $categories, $currentStart, $currentEnd));
            $label      = $currentStart->formatLocalized($format);

            /** @var Category $category */
            foreach ($categories as $category) {
                $labelIn        = $category->id . '-in';
                $labelOut       = $category->id . '-out';
                $labelSumIn     = $category->id . '-total-in';
                $labelSumOut    = $category->id . '-total-out';
                $currentIncome  = $income[$category->id] ?? '0';
                $currentExpense = $expenses[$category->id] ?? '0';


                // add to sum:
                $sumOfIncome[$category->id]  = $sumOfIncome[$category->id] ?? '0';
                $sumOfExpense[$category->id] = $sumOfExpense[$category->id] ?? '0';
                $sumOfIncome[$category->id]  = bcadd($sumOfIncome[$category->id], $currentIncome);
                $sumOfExpense[$category->id] = bcadd($sumOfExpense[$category->id], $currentExpense);

                // add to chart:
                $chartData[$labelIn]['entries'][$label]     = $currentIncome;
                $chartData[$labelOut]['entries'][$label]    = $currentExpense;
                $chartData[$labelSumIn]['entries'][$label]  = $sumOfIncome[$category->id];
                $chartData[$labelSumOut]['entries'][$label] = $sumOfExpense[$category->id];
            }
            $currentStart = clone $currentEnd;
            $currentStart->addDay();
        }
        // remove all empty entries to prevent cluttering:
        $newSet = [];
        foreach ($chartData as $key => $entry) {
            if (!array_sum($entry['entries']) === 0) {
                $newSet[$key] = $chartData[$key];
            }
        }
        if (count($newSet) === 0) {
            $newSet = $chartData;
        }
        $data = $this->generator->multiSet($newSet);
        $cache->store($data);

        return Response::json($data);
    }


    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    private function getExpenses(Collection $accounts, Collection $categories, Carbon $start, Carbon $end): Collection
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->setCategories($categories)->withOpposingAccount();
        $collector->removeFilter(TransferFilter::class);

        $collector->addFilter(OpposingAccountFilter::class);
        $collector->addFilter(PositiveAmountFilter::class);

        $transactions = $collector->getJournals();

        return $transactions;
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    private function getIncome(Collection $accounts, Collection $categories, Carbon $start, Carbon $end): Collection
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
                  ->setCategories($categories)->withOpposingAccount();

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
    private function groupByCategory(Collection $set): array
    {
        // group by category ID:
        $grouped = [];
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $jrnlCatId            = intval($transaction->transaction_journal_category_id);
            $transCatId           = intval($transaction->transaction_category_id);
            $categoryId           = max($jrnlCatId, $transCatId);
            $grouped[$categoryId] = $grouped[$categoryId] ?? '0';
            $grouped[$categoryId] = bcadd($transaction->transaction_amount, $grouped[$categoryId]);
        }

        return $grouped;
    }
}
