<?php
/**
 * ReportHelper.php
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

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Bill as BillCollection;
use FireflyIII\Helpers\Collection\BillLine;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\FiscalHelperInterface;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's exactly 5.
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
        $collector  = app(JournalCollectorInterface::class);
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
            if ($billLine->isActive() || $billLine->isHit()) {
                $collection->addBill($billLine);
            }
        }
        $collection->filterBills();

        return $collection;
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
