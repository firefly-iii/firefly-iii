<?php
/**
 * CategoryController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Report;


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Navigation;

/**
 * Class CategoryController
 *
 * @package FireflyIII\Http\Controllers\Report
 */
class CategoryController extends Controller
{
    /**
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return string
     */
    public function categoryPeriodReport(Carbon $start, Carbon $end, Collection $accounts)
    {
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $categories = $repository->getCategories();
        $data       = [];

        // income only:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)
                  ->withOpposingAccount()
                  ->enableInternalFilter()
                  ->setCategories($categories);

        $transactions = $collector->getJournals();

        // this is the date format we need:
        // define period to group on:
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);

        // this is the set of transactions for this period
        // in these budgets. Now they must be grouped (manually)
        // id, period => amount
        $income = [];
        foreach ($transactions as $transaction) {
            $categoryId = max(intval($transaction->transaction_journal_category_id), intval($transaction->transaction_category_id));
            $date       = $transaction->date->format($carbonFormat);

            if (!isset($income[$categoryId])) {
                $income[$categoryId]['name']    = $this->getCategoryName($categoryId, $categories);
                $income[$categoryId]['sum']     = '0';
                $income[$categoryId]['entries'] = [];
            }

            if (!isset($income[$categoryId]['entries'][$date])) {
                $income[$categoryId]['entries'][$date] = '0';
            }
            $income[$categoryId]['entries'][$date] = bcadd($income[$categoryId]['entries'][$date], $transaction->transaction_amount);
        }

        // and now the same for stuff without a category:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end);
        $collector->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER]);
        $collector->withoutCategory();
        $transactions = $collector->getJournals();

        $income[0]['entries'] = [];
        $income[0]['name']    = strval(trans('firefly.no_category'));
        $income[0]['sum']     = '0';

        foreach ($transactions as $transaction) {
            $date = $transaction->date->format($carbonFormat);

            if (!isset($income[0]['entries'][$date])) {
                $income[0]['entries'][$date] = '0';
            }
            $income[0]['entries'][$date] = bcadd($income[0]['entries'][$date], $transaction->transaction_amount);
        }

        $periods = Navigation::listOfPeriods($start, $end);

        $income = $this->filterCategoryPeriodReport($income);

        $result = view('reports.partials.category-period', compact('categories', 'periods', 'income'))->render();

        return $result;
    }

    /**
     * @param ReportHelperInterface $helper
     * @param Carbon                $start
     * @param Carbon                $end
     * @param Collection            $accounts
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function categoryReport(ReportHelperInterface $helper, Carbon $start, Carbon $end, Collection $accounts)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('category-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get();
        }

        $categories = $helper->getCategoryReport($start, $end, $accounts);

        $result = view('reports.partials.categories', compact('categories'))->render();
        $cache->store($result);

        return $result;
    }

    /**
     * Filters empty results from category period report
     *
     * @param array $data
     *
     * @return array
     */
    private function filterCategoryPeriodReport(array $data): array
    {
        /**
         * @var int   $categoryId
         * @var array $set
         */
        foreach ($data as $categoryId => $set) {
            $sum = '0';
            foreach ($set['entries'] as $amount) {
                $sum = bcadd($amount, $sum);
            }
            $data[$categoryId]['sum'] = $sum;
            if (bccomp('0', $sum) === 0) {
                unset($data[$categoryId]);
            }
        }

        return $data;
    }

    /**
     * @param int        $categoryId
     * @param Collection $categories
     *
     * @return string
     */
    private function getCategoryName(int $categoryId, Collection $categories): string
    {

        $first = $categories->filter(
            function (Category $category) use ($categoryId) {
                return $categoryId === $category->id;
            }
        );
        if (!is_null($first->first())) {
            return $first->first()->name;
        }

        return '(unknown)';
    }

}