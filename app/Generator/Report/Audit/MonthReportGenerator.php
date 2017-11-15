<?php
/**
 * MonthReportGenerator.php
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

namespace FireflyIII\Generator\Report\Audit;

use Carbon\Carbon;
use FireflyIII\Generator\Report\ReportGeneratorInterface;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Steam;

/**
 * Class MonthReportGenerator.
 */
class MonthReportGenerator implements ReportGeneratorInterface
{
    /** @var Collection */
    private $accounts;
    /** @var Carbon */
    private $end;
    /** @var Carbon */
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
            $id             = $account->id;
            $auditData[$id] = $this->getAuditReport($account, $dayBefore);
        }

        $defaultShow = ['icon', 'description', 'balance_before', 'amount', 'balance_after', 'date', 'to'];
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
     * @param Collection $budgets
     *
     * @return ReportGeneratorInterface
     */
    public function setBudgets(Collection $budgets): ReportGeneratorInterface
    {
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

    /**
     * @param Collection $tags
     *
     * @return ReportGeneratorInterface
     */
    public function setTags(Collection $tags): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * @param Account $account
     * @param Carbon  $date
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength) // not that long
     */
    private function getAuditReport(Account $account, Carbon $date): array
    {
        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos = app(CurrencyRepositoryInterface::class);

        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setRange($this->start, $this->end);
        $journals         = $collector->getJournals();
        $journals         = $journals->reverse();
        $dayBeforeBalance = Steam::balance($account, $date);
        $startBalance     = $dayBeforeBalance;
        $currency         = $currencyRepos->find(intval($account->getMeta('currency_id')));

        // @var Transaction $journal
        foreach ($journals as $transaction) {
            $transaction->before = $startBalance;
            $transactionAmount   = $transaction->transaction_amount;

            if ($currency->id === $transaction->foreign_currency_id) {
                $transactionAmount = $transaction->transaction_foreign_amount;
            }

            $newBalance            = bcadd($startBalance, $transactionAmount);
            $transaction->after    = $newBalance;
            $startBalance          = $newBalance;
            $transaction->currency = $currency;
        }

        $return = [
            'journals'         => $journals->reverse(),
            'exists'           => $journals->count() > 0,
            'end'              => $this->end->formatLocalized(strval(trans('config.month_and_day'))),
            'endBalance'       => Steam::balance($account, $this->end),
            'dayBefore'        => $date->formatLocalized(strval(trans('config.month_and_day'))),
            'dayBeforeBalance' => $dayBeforeBalance,
        ];

        return $return;
    }
}
