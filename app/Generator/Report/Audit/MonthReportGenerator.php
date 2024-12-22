<?php

/**
 * MonthReportGenerator.php
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

namespace FireflyIII\Generator\Report\Audit;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Generator\Report\ReportGeneratorInterface;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class MonthReportGenerator.
 */
class MonthReportGenerator implements ReportGeneratorInterface
{
    private Collection $accounts;
    private Carbon     $end;
    private Carbon     $start;

    /**
     * Generates the report.
     *
     * @throws FireflyException
     */
    public function generate(): string
    {
        $auditData   = [];
        $dayBefore   = clone $this->start;
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
        $hideable    = [
            'buttons',
            'icon',
            'description',
            'balance_before',
            'amount',
            'balance_after',
            'date',

            'from',
            'to',
            'budget',
            'category',
            'bill',

            // more new optional fields
            'create_date',
            'update_date',

            // date fields.
            'interest_date',
            'book_date',
            'process_date',
            'due_date',
            'payment_date',
            'invoice_date',
        ];

        try {
            $result = view('reports.audit.report', compact('reportType', 'accountIds', 'auditData', 'hideable', 'defaultShow'))
                ->with('start', $this->start)->with('end', $this->end)->with('accounts', $this->accounts)
                ->render()
            ;
        } catch (\Throwable $e) {
            app('log')->error(sprintf('Cannot render reports.audit.report: %s', $e->getMessage()));
            app('log')->error($e->getTraceAsString());
            $result = sprintf('Could not render report view: %s', $e->getMessage());

            throw new FireflyException($result, 0, $e);
        }

        return $result;
    }

    /**
     * Get the audit report.
     *
     * @throws FireflyException
     */
    public function getAuditReport(Account $account, Carbon $date): array
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accountRepository->setUser($account->user);

        /** @var JournalRepositoryInterface $journalRepository */
        $journalRepository = app(JournalRepositoryInterface::class);
        $journalRepository->setUser($account->user);

        /** @var GroupCollectorInterface $collector */
        $collector         = app(GroupCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setRange($this->start, $this->end)->withAccountInformation()
            ->withBudgetInformation()->withCategoryInformation()->withBillInformation()
        ;
        $journals          = $collector->getExtractedJournals();
        $journals          = array_reverse($journals, true);
        $dayBeforeBalance  = app('steam')->balance($account, $date);
        $startBalance      = $dayBeforeBalance;
        $defaultCurrency   = app('amount')->getDefaultCurrencyByUserGroup($account->user->userGroup);
        $currency          = $accountRepository->getAccountCurrency($account) ?? $defaultCurrency;

        foreach ($journals as $index => $journal) {
            $journals[$index]['balance_before'] = $startBalance;
            $transactionAmount                  = $journal['amount'];

            // make sure amount is in the right "direction".
            if ($account->id === $journal['destination_account_id']) {
                $transactionAmount = app('steam')->positive($journal['amount']);
            }

            if ($currency->id === $journal['foreign_currency_id']) {
                $transactionAmount = $journal['foreign_amount'];
                if ($account->id === $journal['destination_account_id']) {
                    $transactionAmount = app('steam')->positive($journal['foreign_amount']);
                }
            }

            $newBalance                         = bcadd($startBalance, $transactionAmount);
            $journals[$index]['balance_after']  = $newBalance;
            $startBalance                       = $newBalance;

            // add meta dates for each journal.
            $journals[$index]['interest_date']  = $journalRepository->getMetaDateById($journal['transaction_journal_id'], 'interest_date');
            $journals[$index]['book_date']      = $journalRepository->getMetaDateById($journal['transaction_journal_id'], 'book_date');
            $journals[$index]['process_date']   = $journalRepository->getMetaDateById($journal['transaction_journal_id'], 'process_date');
            $journals[$index]['due_date']       = $journalRepository->getMetaDateById($journal['transaction_journal_id'], 'due_date');
            $journals[$index]['payment_date']   = $journalRepository->getMetaDateById($journal['transaction_journal_id'], 'payment_date');
            $journals[$index]['invoice_date']   = $journalRepository->getMetaDateById($journal['transaction_journal_id'], 'invoice_date');
        }
        $locale            = app('steam')->getLocale();

        return [
            'journals'         => $journals,
            'currency'         => $currency,
            'exists'           => 0 !== count($journals),
            'end'              => $this->end->isoFormat((string) trans('config.month_and_day_moment_js', [], $locale)),
            'endBalance'       => app('steam')->balance($account, $this->end),
            'dayBefore'        => $date->isoFormat((string) trans('config.month_and_day_moment_js', [], $locale)),
            'dayBeforeBalance' => $dayBeforeBalance,
        ];
    }

    /**
     * Account collection setter.
     */
    public function setAccounts(Collection $accounts): ReportGeneratorInterface
    {
        $this->accounts = $accounts;

        return $this;
    }

    /**
     * Budget collection setter.
     */
    public function setBudgets(Collection $budgets): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * Category collection setter.
     */
    public function setCategories(Collection $categories): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * End date setter.
     */
    public function setEndDate(Carbon $date): ReportGeneratorInterface
    {
        $this->end = $date;

        return $this;
    }

    /**
     * Expenses collection setter.
     */
    public function setExpense(Collection $expense): ReportGeneratorInterface
    {
        // doesn't use expense collection.
        return $this;
    }

    /**
     * Start date collection setter.
     */
    public function setStartDate(Carbon $date): ReportGeneratorInterface
    {
        $this->start = $date;

        return $this;
    }

    /**
     * Tags collection setter.
     */
    public function setTags(Collection $tags): ReportGeneratorInterface
    {
        return $this;
    }
}
