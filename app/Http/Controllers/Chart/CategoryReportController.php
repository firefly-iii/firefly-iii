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
use FireflyIII\Generator\Chart\Category\CategoryChartGeneratorInterface;
use FireflyIII\Generator\Report\Category\MonthReportGenerator;
use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Response;


/**
 * Separate controller because many helper functions are shared.
 *
 * TODO much of this code is actually repeated. First for the object (category, account), then for the direction (in / out).
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
    /** @var  CategoryChartGeneratorInterface */
    private $generator;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->generator          = app(CategoryChartGeneratorInterface::class);
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
        $names  = [];

        // collect journals (just like the category report does):
        $set     = $this->getExpenses($accounts, $categories, $start, $end);
        $grouped = $this->groupByOpposingAccount($set);

        // show the grouped results:
        $result = [];
        $total  = '0';
        foreach ($grouped as $accountId => $amount) {
            if (!isset($names[$accountId])) {
                $account           = $this->accountRepository->find(intval($accountId));
                $names[$accountId] = $account->name;
            }
            $amount   = bcmul($amount, '-1');
            $total    = bcadd($total, $amount);
            $result[] = ['name' => $names[$accountId], 'id' => $accountId, 'amount' => $amount];
        }

        // also collect all transactions NOT in these categories.
        // TODO include transfers
        if ($others) {
            $collector = new JournalCollector(auth()->user());
            $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL]);
            $journals = $collector->getJournals();
            $sum      = strval($journals->sum('transaction_amount'));
            $sum      = bcmul($sum, '-1');
            Log::debug(sprintf('Sum of others in accountExpense is %f', $sum));
            $sum      = bcsub($sum, $total);
            $result[] = ['name' => trans('firefly.everything_else'), 'id' => 0, 'amount' => $sum];
        }

        $data = $this->generator->pieChart($result);

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
        $names  = [];

        // collect journals (just like the category report does):
        $set     = $this->getIncome($accounts, $categories, $start, $end);
        $grouped = $this->groupByOpposingAccount($set);

        // loop and show the grouped results:
        $result = [];
        $total  = '0';
        foreach ($grouped as $accountId => $amount) {
            if (!isset($names[$accountId])) {
                $account           = $this->accountRepository->find(intval($accountId));
                $names[$accountId] = $account->name;
            }
            $total    = bcadd($total, $amount);
            $result[] = ['name' => $names[$accountId], 'id' => $accountId, 'amount' => $amount];
        }

        // also collect others?
        // TODO include transfers
        if ($others) {
            $collector = new JournalCollector(auth()->user());
            $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::DEPOSIT]);
            $journals = $collector->getJournals();
            $sum      = strval($journals->sum('transaction_amount'));
            Log::debug(sprintf('Sum of others in accountIncome is %f', $sum));
            $sum      = bcsub($sum, $total);
            $result[] = ['name' => trans('firefly.everything_else'), 'id' => 0, 'amount' => $sum];
        }

        $data = $this->generator->pieChart($result);

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
        $names  = [];

        // collect journals (just like the category report does):
        $set     = $this->getExpenses($accounts, $categories, $start, $end);
        $grouped = $this->groupByCategory($set);

        // show the grouped results:
        $result = [];
        $total  = '0';
        foreach ($grouped as $categoryId => $amount) {
            if (!isset($names[$categoryId])) {
                $category           = $this->categoryRepository->find(intval($categoryId));
                $names[$categoryId] = $category->name;
            }
            $amount   = bcmul($amount, '-1');
            $total    = bcadd($total, $amount);
            $result[] = ['name' => $names[$categoryId], 'id' => $categoryId, 'amount' => $amount];
        }

        // also collect all transactions NOT in these categories.
        // TODO include transfers
        if ($others) {
            $collector = new JournalCollector(auth()->user());
            $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL]);
            $journals = $collector->getJournals();
            $sum      = strval($journals->sum('transaction_amount'));
            $sum      = bcmul($sum, '-1');
            Log::debug(sprintf('Sum of others in categoryExpense is %f', $sum));
            $sum      = bcsub($sum, $total);
            $result[] = ['name' => trans('firefly.everything_else'), 'id' => 0, 'amount' => $sum];
        }

        $data = $this->generator->pieChart($result);

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
        $names  = [];

        // collect journals (just like the category report does):
        $set     = $this->getIncome($accounts, $categories, $start, $end);
        $grouped = $this->groupByCategory($set);

        // loop and show the grouped results:
        $result = [];
        $total  = '0';
        foreach ($grouped as $categoryId => $amount) {
            if (!isset($names[$categoryId])) {
                $category           = $this->categoryRepository->find(intval($categoryId));
                $names[$categoryId] = $category->name;
            }
            $total    = bcadd($total, $amount);
            $result[] = ['name' => $names[$categoryId], 'id' => $categoryId, 'amount' => $amount];
        }

        // also collect others?
        // TODO include transfers
        if ($others) {
            $collector = new JournalCollector(auth()->user());
            $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::DEPOSIT]);
            $journals = $collector->getJournals();
            $sum      = strval($journals->sum('transaction_amount'));
            Log::debug(sprintf('Sum of others in categoryIncome is %f', $sum));
            $sum      = bcsub($sum, $total);
            $result[] = ['name' => trans('firefly.everything_else'), 'id' => 0, 'amount' => $sum];
        }

        $data = $this->generator->pieChart($result);

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
        // determin optimal period:
        $period   = '1D';
        $format   = 'month_and_day';
        $function = 'endOfDay';
        if ($start->diffInMonths($end) > 1) {
            $period   = '1M';
            $format   = 'month';
            $function = 'endOfMonth';
        }
        if ($start->diffInMonths($end) > 13) {
            $period   = '1Y';
            $format   = 'year';
            $function = 'endOfYear';
        }
        Log::debug(sprintf('Period is %s', $period));
        $data         = [];
        $currentStart = clone $start;
        while ($currentStart < $end) {
            $currentEnd = clone $currentStart;
            Log::debug(sprintf('Function is %s', $function));
            $currentEnd = $currentEnd->$function();
            //$currentEnd = Navigation::endOfPeriod($current, $period);
            $expenses = $this->groupByCategory($this->getExpenses($accounts, $categories, $currentStart, $currentEnd));
            $income   = $this->groupByCategory($this->getIncome($accounts, $categories, $currentStart, $currentEnd));
            $label    = $currentStart->formatLocalized(strval(trans('config.' . $format)));

            Log::debug(sprintf('Now grabbing CMC expenses between %s and %s', $currentStart->format('Y-m-d'), $currentEnd->format('Y-m-d')));

            $data[$label] = [
                'in'  => [],
                'out' => [],
            ];

            /** @var Category $category */
            foreach ($categories as $category) {
                // get sum, and get label:
                $categoryId                        = $category->id;
                $data[$label]['name'][$categoryId] = $category->name;
                $data[$label]['in'][$categoryId]   = $income[$categoryId] ?? '0';
                $data[$label]['out'][$categoryId]  = $expenses[$categoryId] ?? '0';
            }

            $currentStart = clone $currentEnd;
            $currentStart->addDay();// = Navigation::addPeriod($current, $period, 0);
        }

        $data = $this->generator->mainReportChart($data);

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
        $collector = new JournalCollector(auth()->user());
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
        $collector = new JournalCollector(auth()->user());
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