<?php
/**
 * MonthReportGenerator.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Generator\Report\Audit;


use Carbon\Carbon;
use FireflyIII\Generator\Report\ReportGeneratorInterface;
use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use Illuminate\Support\Collection;
use Steam;

/**
 * Class MonthReportGenerator
 *
 * @package FireflyIII\Generator\Report\Standard
 */
class MonthReportGenerator implements ReportGeneratorInterface
{
    /** @var  Collection */
    private $accounts;
    /** @var  Carbon */
    private $end;
    /** @var  Carbon */
    private $start;

    /**
     * @return string
     */
    public function generate(): string
    {


        $auditData = [];
        $dayBefore = clone $this->start;
        $dayBefore->subDay();
        /** @var Account $account */
        foreach ($this->accounts as $account) {
            // balance the day before:
            $id               = $account->id;
            $dayBeforeBalance = Steam::balance($account, $dayBefore);
            $collector        = new JournalCollector(auth()->user());
            $collector->setAccounts(new Collection([$account]))->setRange($this->start, $this->end);
            $journals     = $collector->getJournals();
            $journals     = $journals->reverse();
            $startBalance = $dayBeforeBalance;


            /** @var Transaction $journal */
            foreach ($journals as $transaction) {
                $transaction->before = $startBalance;
                $transactionAmount   = $transaction->transaction_amount;
                $newBalance          = bcadd($startBalance, $transactionAmount);
                $transaction->after  = $newBalance;
                $startBalance        = $newBalance;
            }

            /*
             * Reverse set again.
             */
            $auditData[$id]['journals']         = $journals->reverse();
            $auditData[$id]['exists']           = $journals->count() > 0;
            $auditData[$id]['end']              = $this->end->formatLocalized(strval(trans('config.month_and_day')));
            $auditData[$id]['endBalance']       = Steam::balance($account, $this->end);
            $auditData[$id]['dayBefore']        = $dayBefore->formatLocalized(strval(trans('config.month_and_day')));
            $auditData[$id]['dayBeforeBalance'] = $dayBeforeBalance;
        }

        $reportType  = 'audit';
        $accountIds  = join(',', $this->accounts->pluck('id')->toArray());
        $hideable    = ['buttons', 'icon', 'description', 'balance_before', 'amount', 'balance_after', 'date',
                        'interest_date', 'book_date', 'process_date',
                        // three new optional fields.
                        'due_date', 'payment_date', 'invoice_date',
                        'from', 'to', 'budget', 'category', 'bill',
                        // more new optional fields
                        'internal_reference', 'notes',

                        'create_date', 'update_date',
        ];
        $defaultShow = ['icon', 'description', 'balance_before', 'amount', 'balance_after', 'date', 'to'];

        return view('reports.audit.report', compact('reportType', 'accountIds', 'auditData', 'hideable', 'defaultShow'))
            ->with('start', $this->start)->with('end', $this->end)->with('accounts', $this->accounts)
            ->render();

    }

    /**
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
     * @param Collection $categories
     *
     * @return ReportGeneratorInterface
     */
    public function setCategories(Collection $categories): ReportGeneratorInterface
    {
        return $this;
    }

    /**
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
     * @param Carbon $date
     *
     * @return ReportGeneratorInterface
     */
    public function setStartDate(Carbon $date): ReportGeneratorInterface
    {
        $this->start = $date;

        return $this;
    }
}