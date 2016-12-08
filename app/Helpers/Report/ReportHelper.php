<?php
/**
 * ReportHelper.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Bill as BillCollection;
use FireflyIII\Helpers\Collection\BillLine;
use FireflyIII\Helpers\Collection\Category as CategoryCollection;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\FiscalHelperInterface;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class ReportHelper
 *
 * @package FireflyIII\Helpers\Report
 */
class ReportHelper implements ReportHelperInterface
{

    /** @var  BudgetRepositoryInterface */
    protected $budgetRepository;

    /**
     * ReportHelper constructor.
     *
     *
     * @param BudgetRepositoryInterface $budgetRepository
     */
    public function __construct(BudgetRepositoryInterface $budgetRepository)
    {
        $this->budgetRepository = $budgetRepository;
    }

    /**
     * This method generates a full report for the given period on all
     * the users bills and their payments.
     *
     * Excludes bills which have not had a payment on the mentioned accounts.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return BillCollection
     */
    public function getBillReport(Carbon $start, Carbon $end, Collection $accounts): BillCollection
    {
        /** @var BillRepositoryInterface $repository */
        $repository = app(BillRepositoryInterface::class);
        $bills      = $repository->getBillsForAccounts($accounts);
        $collector  = app(JournalCollectorInterface::class, [auth()->user()]);
        $collector->setAccounts($accounts)->setRange($start, $end)->setBills($bills);
        $journals   = $collector->getJournals();
        $collection = new BillCollection;
        $collection->setStartDate($start);
        $collection->setEndDate($end);

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            $billLine = new BillLine;
            $billLine->setBill($bill);
            $billLine->setMin(strval($bill->amount_min));
            $billLine->setMax(strval($bill->amount_max));
            $billLine->setHit(false);
            // is hit in period?

            $entry = $journals->filter(
                function (Transaction $transaction) use ($bill) {
                    return $transaction->bill_id === $bill->id;
                }
            );
            $first = $entry->first();
            if (!is_null($first)) {
                $billLine->setTransactionJournalId($first->id);
                $billLine->setAmount($first->transaction_amount);
                $billLine->setLastHitDate($first->date);
                $billLine->setHit(true);
            }

            // bill is active, or bill is hit:
            if ($billLine->isActive() || $billLine->isHit()) {
                $collection->addBill($billLine);
            }
        }

        // do some extra filtering.
        $collection->filterBills();

        return $collection;
    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return CategoryCollection
     */
    public function getCategoryReport(Collection $accounts, Carbon $start, Carbon $end): CategoryCollection
    {
        $object = new CategoryCollection;
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $categories = $repository->getCategories();

        /** @var Category $category */
        foreach ($categories as $category) {
            $spent = $repository->spentInPeriod(new Collection([$category]), $accounts, $start, $end);
            // CategoryCollection expects the amount in $spent:
            $category->spent = $spent;
            $object->addCategory($category);
        }

        return $object;
    }

    /**
     * @param Carbon $date
     *
     * @return array
     */
    public function listOfMonths(Carbon $date): array
    {
        /** @var FiscalHelperInterface $fiscalHelper */
        $fiscalHelper = app(FiscalHelperInterface::class);
        $start        = clone $date;
        $start->startOfMonth();
        $end = Carbon::now();
        $end->endOfMonth();
        $months = [];

        while ($start <= $end) {
            $year = $fiscalHelper->endOfFiscalYear($start)->year; // current year
            if (!isset($months[$year])) {
                $months[$year] = [
                    'fiscal_start' => $fiscalHelper->startOfFiscalYear($start)->format('Y-m-d'),
                    'fiscal_end'   => $fiscalHelper->endOfFiscalYear($start)->format('Y-m-d'),
                    'start'        => Carbon::createFromDate($year, 1, 1)->format('Y-m-d'),
                    'end'          => Carbon::createFromDate($year, 12, 31)->format('Y-m-d'),
                    'months'       => [],
                ];
            }

            $currentEnd = clone $start;
            $currentEnd->endOfMonth();
            $months[$year]['months'][] = [
                'formatted' => $start->formatLocalized('%B %Y'),
                'start'     => $start->format('Y-m-d'),
                'end'       => $currentEnd->format('Y-m-d'),
                'month'     => $start->month,
                'year'      => $year,
            ];

            $start = clone $currentEnd; // to make the hop to the next month properly
            $start->addDay();
        }

        return $months;
    }

}
