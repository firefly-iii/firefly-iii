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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

/** @noinspection PhpUndefinedMethodInspection */

declare(strict_types=1);

namespace FireflyIII\Generator\Report\Audit;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Generator\Report\ReportGeneratorInterface;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Throwable;

/**
 * Class MonthReportGenerator.
 */
class MonthReportGenerator implements ReportGeneratorInterface
{
    /** @var Collection The accounts used. */
    private $accounts;
    /** @var Carbon End date of the report. */
    private $end;
    /** @var Carbon Start date of the report. */
    private $start;

    /**
     * Generates the report.
     *
     * @return string
     * @throws FireflyException
     * @codeCoverageIgnore
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
        $accountIds  = implode(',', $this->accounts->pluck('id')->toArray());
        $hideable    = ['buttons', 'icon', 'description', 'balance_before', 'amount', 'balance_after', 'date',
                        'interest_date', 'book_date', 'process_date',
                        // three new optional fields.
                        'due_date', 'payment_date', 'invoice_date',
                        'from', 'to', 'budget', 'category', 'bill',
                        // more new optional fields
                        'internal_reference', 'notes',
                        'create_date', 'update_date',
        ];
        try {
            $result = view('reports.audit.report', compact('reportType', 'accountIds', 'auditData', 'hideable', 'defaultShow'))
                ->with('start', $this->start)->with('end', $this->end)->with('accounts', $this->accounts)
                ->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Cannot render reports.audit.report: %s', $e->getMessage()));
            $result = 'Could not render report view.';
        }

        return $result;
    }

    /**
     * Get the audit report.
     *
     * @param Account $account
     * @param Carbon  $date
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength) // not that long
     * @throws FireflyException
     */
    public function getAuditReport(Account $account, Carbon $date): array
    {
        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos = app(CurrencyRepositoryInterface::class);

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accountRepository->setUser($account->user);

        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setRange($this->start, $this->end);
        $journals         = $collector->getTransactions();
        $journals         = $journals->reverse();
        $dayBeforeBalance = app('steam')->balance($account, $date);
        $startBalance     = $dayBeforeBalance;
        $currency         = $currencyRepos->findNull((int)$accountRepository->getMetaValue($account, 'currency_id'));

        if (null === $currency) {
            throw new FireflyException('Unexpected NULL value in account currency preference.');
        }

        /** @var Transaction $transaction */
        foreach ($journals as $transaction) {
            $transaction->before = $startBalance;
            $transactionAmount   = $transaction->transaction_amount;

            if ($currency->id === $transaction->foreign_currency_id) {
                $transactionAmount = $transaction->transaction_foreign_amount;
            }

            $newBalance         = bcadd($startBalance, $transactionAmount);
            $transaction->after = $newBalance;
            $startBalance       = $newBalance;
        }

        $return = [
            'journals'         => $journals->reverse(),
            'exists'           => $journals->count() > 0,
            'end'              => $this->end->formatLocalized((string)trans('config.month_and_day')),
            'endBalance'       => app('steam')->balance($account, $this->end),
            'dayBefore'        => $date->formatLocalized((string)trans('config.month_and_day')),
            'dayBeforeBalance' => $dayBeforeBalance,
        ];

        return $return;
    }

    /**
     * Account collection setter.
     *
     * @param Collection $accounts
     *
     * @return ReportGeneratorInterface
     * @codeCoverageIgnore
     */
    public function setAccounts(Collection $accounts): ReportGeneratorInterface
    {
        $this->accounts = $accounts;

        return $this;
    }

    /**
     * Budget collection setter.
     *
     * @param Collection $budgets
     *
     * @return ReportGeneratorInterface
     * @codeCoverageIgnore
     */
    public function setBudgets(Collection $budgets): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Category collection setter.
     *
     * @param Collection $categories
     *
     * @return ReportGeneratorInterface
     * @codeCoverageIgnore
     */
    public function setCategories(Collection $categories): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * End date setter.
     *
     * @param Carbon $date
     *
     * @return ReportGeneratorInterface
     * @codeCoverageIgnore
     */
    public function setEndDate(Carbon $date): ReportGeneratorInterface
    {
        $this->end = $date;

        return $this;
    }

    /**
     * Expenses collection setter.
     *
     * @param Collection $expense
     *
     * @return ReportGeneratorInterface
     * @codeCoverageIgnore
     */
    public function setExpense(Collection $expense): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Start date collection setter.
     *
     * @param Carbon $date
     *
     * @return ReportGeneratorInterface
     * @codeCoverageIgnore
     */
    public function setStartDate(Carbon $date): ReportGeneratorInterface
    {
        $this->start = $date;

        return $this;
    }

    /**
     * Tags collection setter.
     *
     * @param Collection $tags
     *
     * @return ReportGeneratorInterface
     * @codeCoverageIgnore
     */
    public function setTags(Collection $tags): ReportGeneratorInterface
    {
        return $this;
    }
}
