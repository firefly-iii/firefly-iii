<?php

/**
 * ReportHelper.php
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

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class ReportHelper.
 */
class ReportHelper implements ReportHelperInterface
{
    /** @var BudgetRepositoryInterface The budget repository */
    protected $budgetRepository;

    /**
     * ReportHelper constructor.
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
     */
    public function getBillReport(Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var BillRepositoryInterface $repository */
        $repository = app(BillRepositoryInterface::class);
        $bills      = $repository->getBillsForAccounts($accounts);
        $report     = [
            'bills' => [],
        ];

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            $expectedDates            = $repository->getPayDatesInRange($bill, $start, $end);
            $billId                   = $bill->id;
            $currency                 = $bill->transactionCurrency;
            $current                  = [
                'id'                      => $bill->id,
                'name'                    => $bill->name,
                'active'                  => $bill->active,
                'amount_min'              => $bill->amount_min,
                'amount_max'              => $bill->amount_max,
                'currency_id'             => $bill->transaction_currency_id,
                'currency_code'           => $currency->code,
                'currency_name'           => $currency->name,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'expected_dates'          => $expectedDates->toArray(),
                'paid_moments'            => [],
            ];

            /** @var Carbon $expectedStart */
            foreach ($expectedDates as $expectedStart) {
                $expectedEnd               = app('navigation')->endOfX($expectedStart, $bill->repeat_freq, null);

                // is paid in this period maybe?
                /** @var GroupCollectorInterface $collector */
                $collector                 = app(GroupCollectorInterface::class);
                $collector->setAccounts($accounts)->setRange($expectedStart, $expectedEnd)->setBill($bill);
                $current['paid_moments'][] = $collector->getExtractedJournals();
            }

            // append to report:
            $report['bills'][$billId] = $current;
        }

        return $report;
    }

    /**
     * Generate a list of months for the report.
     */
    public function listOfMonths(Carbon $date): array
    {
        /** @var FiscalHelperInterface $fiscalHelper */
        $fiscalHelper = app(FiscalHelperInterface::class);
        $start        = clone $date;
        $start->startOfMonth();
        $end          = today(config('app.timezone'));
        $end->endOfMonth();
        $months       = [];

        while ($start <= $end) {
            $year                      = $fiscalHelper->endOfFiscalYear($start)->year; // current year
            if (!array_key_exists($year, $months)) {
                $months[$year] = [
                    'fiscal_start' => $fiscalHelper->startOfFiscalYear($start)->format('Y-m-d'),
                    'fiscal_end'   => $fiscalHelper->endOfFiscalYear($start)->format('Y-m-d'),
                    'start'        => Carbon::createFromDate($year, 1, 1)->format('Y-m-d'),
                    'end'          => Carbon::createFromDate($year, 12, 31)->format('Y-m-d'),
                    'months'       => [],
                ];
            }

            $currentEnd                = clone $start;
            $currentEnd->endOfMonth();
            $months[$year]['months'][] = [
                'formatted' => $start->isoFormat((string) trans('config.month_js')),
                'start'     => $start->format('Y-m-d'),
                'end'       => $currentEnd->format('Y-m-d'),
                'month'     => $start->month,
                'year'      => $year,
            ];

            $start                     = clone $currentEnd; // to make the hop to the next month properly
            $start->addDay();
        }

        return $months;
    }
}
