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

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Chart;


use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Generator\Report\Category\MonthReportGenerator;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
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

    /** @var AccountRepositoryInterface */
    private $accountRepository;
    /** @var CategoryRepositoryInterface */
    private $categoryRepository;
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
                $this->generator          = app(GeneratorInterface::class);
                $this->categoryRepository = app(CategoryRepositoryInterface::class);
                $this->accountRepository  = app(AccountRepositoryInterface::class);

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
        /** @var bool $others */
        $others = intval($others) === 1;
        $cache  = new CacheProperties;
        $cache->addProperty('chart.category.report.account-expense');
        $cache->addProperty($accounts);
        $cache->addProperty($categories);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($others);
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $names     = [];
        $set       = $this->getExpenses($accounts, $categories, $start, $end);
        $grouped   = $this->groupByOpposingAccount($set);
        $chartData = [];
        $total     = '0';

        foreach ($grouped as $accountId => $amount) {
            if (!isset($names[$accountId])) {
                $account           = $this->accountRepository->find(intval($accountId));
                $names[$accountId] = $account->name;
            }
            $amount                        = bcmul($amount, '-1');
            $total                         = bcadd($total, $amount);
            $chartData[$names[$accountId]] = $amount;
        }

        // also collect all transactions NOT in these categories.
        if ($others) {
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class, [auth()->user()]);
            $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL]);
            $journals                                            = $collector->getJournals();
            $sum                                                 = strval($journals->sum('transaction_amount'));
            $sum                                                 = bcmul($sum, '-1');
            $sum                                                 = bcsub($sum, $total);
            $chartData[strval(trans('firefly.everything_else'))] = $sum;
        }

        $data = $this->generator->pieChart($chartData);
        $cache->store($data);

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
        /** @var bool $others */
        $others = intval($others) === 1;
        $cache  = new CacheProperties;
        $cache->addProperty('chart.category.report.account-income');
        $cache->addProperty($accounts);
        $cache->addProperty($categories);
        $cache->addProperty($start);
        $cache->addProperty($others);
        $cache->addProperty($end);
        if ($cache->has()) {
            return Response::json($cache->get());
        }


        $names     = [];
        $set       = $this->getIncome($accounts, $categories, $start, $end);
        $grouped   = $this->groupByOpposingAccount($set);
        $chartData = [];
        $total     = '0';

        foreach ($grouped as $accountId => $amount) {
            if (!isset($names[$accountId])) {
                $account           = $this->accountRepository->find(intval($accountId));
                $names[$accountId] = $account->name;
            }
            $total                         = bcadd($total, $amount);
            $chartData[$names[$accountId]] = $amount;
        }

        // also collect others?
        if ($others) {
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class, [auth()->user()]);
            $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::DEPOSIT]);
            $journals                                            = $collector->getJournals();
            $sum                                                 = strval($journals->sum('transaction_amount'));
            $sum                                                 = bcsub($sum, $total);
            $chartData[strval(trans('firefly.everything_else'))] = $sum;
        }

        $data = $this->generator->pieChart($chartData);
        $cache->store($data);

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
        /** @var bool $others */
        $others = intval($others) === 1;
        $cache  = new CacheProperties;
        $cache->addProperty('chart.category.report.category-expense');
        $cache->addProperty($accounts);
        $cache->addProperty($categories);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($others);
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $names     = [];
        $set       = $this->getExpenses($accounts, $categories, $start, $end);
        $grouped   = $this->groupByCategory($set);
        $total     = '0';
        $chartData = [];

        foreach ($grouped as $categoryId => $amount) {
            if (!isset($names[$categoryId])) {
                $category           = $this->categoryRepository->find(intval($categoryId));
                $names[$categoryId] = $category->name;
            }
            $amount                         = bcmul($amount, '-1');
            $total                          = bcadd($total, $amount);
            $chartData[$names[$categoryId]] = $amount;
        }

        // also collect all transactions NOT in these categories.
        if ($others) {
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class, [auth()->user()]);
            $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL]);
            $journals                                            = $collector->getJournals();
            $sum                                                 = strval($journals->sum('transaction_amount'));
            $sum                                                 = bcmul($sum, '-1');
            $sum                                                 = bcsub($sum, $total);
            $chartData[strval(trans('firefly.everything_else'))] = $sum;
        }

        $data = $this->generator->pieChart($chartData);
        $cache->store($data);

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
        /** @var bool $others */
        $others = intval($others) === 1;
        $cache  = new CacheProperties;
        $cache->addProperty('chart.category.report.category-income');
        $cache->addProperty($accounts);
        $cache->addProperty($categories);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($others);
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $names     = [];
        $set       = $this->getIncome($accounts, $categories, $start, $end);
        $grouped   = $this->groupByCategory($set);
        $total     = '0';
        $chartData = [];

        foreach ($grouped as $categoryId => $amount) {
            if (!isset($names[$categoryId])) {
                $category           = $this->categoryRepository->find(intval($categoryId));
                $names[$categoryId] = $category->name;
            }
            $total                          = bcadd($total, $amount);
            $chartData[$names[$categoryId]] = $amount;
        }

        if ($others) {
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class, [auth()->user()]);
            $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::DEPOSIT]);
            $journals                                            = $collector->getJournals();
            $sum                                                 = strval($journals->sum('transaction_amount'));
            $sum                                                 = bcsub($sum, $total);
            $chartData[strval(trans('firefly.everything_else'))] = $sum;
        }

        $data = $this->generator->pieChart($chartData);
        $cache->store($data);

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
            return Response::json($cache->get());
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
            if (!array_sum($entry['entries']) == 0) {
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
        $collector = app(JournalCollectorInterface::class, [auth()->user()]);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->setCategories($categories)->withOpposingAccount()->disableFilter();
        $accountIds   = $accounts->pluck('id')->toArray();
        $transactions = $collector->getJournals();
        $set          = MonthReportGenerator::filterExpenses($transactions, $accountIds);

        return $set;
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
        $collector = app(JournalCollectorInterface::class, [auth()->user()]);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
                  ->setCategories($categories)->withOpposingAccount();
        $accountIds   = $accounts->pluck('id')->toArray();
        $transactions = $collector->getJournals();
        $set          = MonthReportGenerator::filterIncome($transactions, $accountIds);

        return $set;
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

    /**
     * @param Collection $set
     *
     * @return array
     */
    private function groupByOpposingAccount(Collection $set): array
    {
        $grouped = [];
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $accountId           = $transaction->opposing_account_id;
            $grouped[$accountId] = $grouped[$accountId] ?? '0';
            $grouped[$accountId] = bcadd($transaction->transaction_amount, $grouped[$accountId]);
        }

        return $grouped;
    }
}
