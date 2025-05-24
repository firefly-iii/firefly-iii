<?php

/*
 * BudgetReportGenerator.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Support\Report\Budget;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\NoBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class BudgetReportGenerator
 *
 * This class is basically a very long for-each loop disguised as a class. It's readable but not really OOP.
 */
class BudgetReportGenerator
{
    private Collection                              $accounts;
    private readonly BudgetLimitRepositoryInterface $blRepository;
    private Collection                              $budgets;
    private TransactionCurrency                     $currency;
    private Carbon                                  $end;
    private readonly NoBudgetRepositoryInterface    $nbRepository;
    private readonly OperationsRepositoryInterface  $opsRepository;
    private array                                   $report;
    private readonly BudgetRepositoryInterface      $repository;
    private Carbon                                  $start;

    /**
     * BudgetReportGenerator constructor.
     */
    public function __construct()
    {
        $this->repository    = app(BudgetRepositoryInterface::class);
        $this->blRepository  = app(BudgetLimitRepositoryInterface::class);
        $this->opsRepository = app(OperationsRepositoryInterface::class);
        $this->nbRepository  = app(NoBudgetRepositoryInterface::class);
        $this->report        = [];
    }

    /**
     * Returns the data necessary for the "account per budget" block on the budget report.
     */
    public function accountPerBudget(): void
    {
        $spent        = $this->opsRepository->listExpenses($this->start, $this->end, $this->accounts, $this->budgets);
        $this->report = [];

        /** @var Account $account */
        foreach ($this->accounts as $account) {
            $accountId = $account->id;
            $this->report[$accountId] ??= [
                'name'       => $account->name,
                'id'         => $account->id,
                'iban'       => $account->iban,
                'currencies' => [],
            ];
        }

        // loop expenses.
        foreach ($spent as $currency) {
            $this->processExpenses($currency);
        }
    }

    /**
     * Process each row of expenses collected for the "Account per budget" partial
     */
    private function processExpenses(array $expenses): void
    {
        foreach ($expenses['budgets'] as $budget) {
            $this->processBudgetExpenses($expenses, $budget);
        }
    }

    /**
     * Process each set of transactions for each row of expenses.
     */
    private function processBudgetExpenses(array $expenses, array $budget): void
    {
        $budgetId   = (int) $budget['id'];
        $currencyId = (int) $expenses['currency_id'];
        foreach ($budget['transaction_journals'] as $journal) {
            $sourceAccountId = $journal['source_account_id'];

            $this->report[$sourceAccountId]['currencies'][$currencyId]
                ??= [
                    'currency_id'             => $expenses['currency_id'],
                    'currency_symbol'         => $expenses['currency_symbol'],
                    'currency_name'           => $expenses['currency_name'],
                    'currency_decimal_places' => $expenses['currency_decimal_places'],
                    'budgets'                 => [],
                ];

            $this->report[$sourceAccountId]['currencies'][$currencyId]['budgets'][$budgetId]
                ??= '0';

            $this->report[$sourceAccountId]['currencies'][$currencyId]['budgets'][$budgetId]
                             = bcadd($this->report[$sourceAccountId]['currencies'][$currencyId]['budgets'][$budgetId], (string) $journal['amount']);
        }
    }

    /**
     * Generates the data necessary to create the card that displays
     * the budget overview in the general report.
     */
    public function general(): void
    {
        $this->report = [
            'budgets' => [],
            'sums'    => [],
        ];

        $this->generalBudgetReport();
        $this->noBudgetReport();
        $this->percentageReport();
    }

    /**
     * Start the budgets block on the default report by processing every budget.
     */
    private function generalBudgetReport(): void
    {
        $budgetList = $this->repository->getBudgets();

        /** @var Budget $budget */
        foreach ($budgetList as $budget) {
            $this->processBudget($budget);
        }
    }

    /**
     * Process expenses etc. for a single budget for the budgets block on the default report.
     */
    private function processBudget(Budget $budget): void
    {
        $budgetId = $budget->id;
        $this->report['budgets'][$budgetId] ??= [
            'budget_id'     => $budgetId,
            'budget_name'   => $budget->name,
            'no_budget'     => false,
            'budget_limits' => [],
        ];

        // get all budget limits for budget in period:
        $limits   = $this->blRepository->getBudgetLimits($budget, $this->start, $this->end);

        /** @var BudgetLimit $limit */
        foreach ($limits as $limit) {
            $this->processLimit($budget, $limit);
        }
    }

    /**
     * Process a single budget limit for the budgets block on the default report.
     */
    private function processLimit(Budget $budget, BudgetLimit $limit): void
    {
        $budgetId                                       = $budget->id;
        $limitId                                        = $limit->id;
        $limitCurrency                                  = $limit->transactionCurrency ?? $this->currency;
        $currencyId                                     = $limitCurrency->id;
        $expenses                                       = $this->opsRepository->sumExpenses($limit->start_date, $limit->end_date, $this->accounts, new Collection([$budget]));
        $spent                                          = $expenses[$currencyId]['sum'] ?? '0';
        $left                                           = -1 === bccomp(bcadd($limit->amount, $spent), '0') ? '0' : bcadd($limit->amount, $spent);
        $overspent                                      = 1 === bccomp(bcmul($spent, '-1'), $limit->amount) ? bcadd($spent, $limit->amount) : '0';

        $this->report['budgets'][$budgetId]['budget_limits'][$limitId] ??= [
            'budget_limit_id'         => $limitId,
            'start_date'              => $limit->start_date,
            'end_date'                => $limit->end_date,
            'budgeted'                => $limit->amount,
            'budgeted_pct'            => '0',
            'spent'                   => $spent,
            'spent_pct'               => '0',
            'left'                    => $left,
            'overspent'               => $overspent,
            'currency_id'             => $currencyId,
            'currency_code'           => $limitCurrency->code,
            'currency_name'           => $limitCurrency->name,
            'currency_symbol'         => $limitCurrency->symbol,
            'currency_decimal_places' => $limitCurrency->decimal_places,
        ];

        // make sum information:
        $this->report['sums'][$currencyId]
                                                                       ??= [
                                                                           'budgeted'                => '0',
                                                                           'spent'                   => '0',
                                                                           'left'                    => '0',
                                                                           'overspent'               => '0',
                                                                           'currency_id'             => $currencyId,
                                                                           'currency_code'           => $limitCurrency->code,
                                                                           'currency_name'           => $limitCurrency->name,
                                                                           'currency_symbol'         => $limitCurrency->symbol,
                                                                           'currency_decimal_places' => $limitCurrency->decimal_places,
                                                                       ];
        $this->report['sums'][$currencyId]['budgeted']  = bcadd((string) $this->report['sums'][$currencyId]['budgeted'], $limit->amount);
        $this->report['sums'][$currencyId]['spent']     = bcadd((string) $this->report['sums'][$currencyId]['spent'], $spent);
        $this->report['sums'][$currencyId]['left']      = bcadd((string) $this->report['sums'][$currencyId]['left'], bcadd($limit->amount, $spent));
        $this->report['sums'][$currencyId]['overspent'] = bcadd((string) $this->report['sums'][$currencyId]['overspent'], $overspent);
    }

    /**
     * Calculate the expenses for transactions without a budget. Part of the "budgets" block of the default report.
     */
    private function noBudgetReport(): void
    {
        // add no budget info.
        $this->report['budgets'][0] = [
            'budget_id'     => null,
            'budget_name'   => null,
            'no_budget'     => true,
            'budget_limits' => [],
        ];

        $noBudget                   = $this->nbRepository->sumExpenses($this->start, $this->end, $this->accounts);
        foreach ($noBudget as $noBudgetEntry) {
            // currency information:
            $nbCurrencyId                                                   = (int) ($noBudgetEntry['currency_id'] ?? $this->currency->id);
            $nbCurrencyCode                                                 = $noBudgetEntry['currency_code'] ?? $this->currency->code;
            $nbCurrencyName                                                 = $noBudgetEntry['currency_name'] ?? $this->currency->name;
            $nbCurrencySymbol                                               = $noBudgetEntry['currency_symbol'] ?? $this->currency->symbol;
            $nbCurrencyDp                                                   = $noBudgetEntry['currency_decimal_places'] ?? $this->currency->decimal_places;

            $this->report['budgets'][0]['budget_limits'][]                  = [
                'budget_limit_id'         => null,
                'start_date'              => $this->start,
                'end_date'                => $this->end,
                'budgeted'                => '0',
                'budgeted_pct'            => '0',
                'spent'                   => $noBudgetEntry['sum'],
                'spent_pct'               => '0',
                'left'                    => '0',
                'overspent'               => '0',
                'currency_id'             => $nbCurrencyId,
                'currency_code'           => $nbCurrencyCode,
                'currency_name'           => $nbCurrencyName,
                'currency_symbol'         => $nbCurrencySymbol,
                'currency_decimal_places' => $nbCurrencyDp,
            ];
            $this->report['sums'][$nbCurrencyId]['spent']                   = bcadd($this->report['sums'][$nbCurrencyId]['spent'] ?? '0', (string) $noBudgetEntry['sum']);
            // append currency info because it may be missing:
            $this->report['sums'][$nbCurrencyId]['currency_id']             = $nbCurrencyId;
            $this->report['sums'][$nbCurrencyId]['currency_code']           = $nbCurrencyCode;
            $this->report['sums'][$nbCurrencyId]['currency_name']           = $nbCurrencyName;
            $this->report['sums'][$nbCurrencyId]['currency_symbol']         = $nbCurrencySymbol;
            $this->report['sums'][$nbCurrencyId]['currency_decimal_places'] = $nbCurrencyDp;

            // append other sums because they might be missing:
            $this->report['sums'][$nbCurrencyId]['overspent'] ??= '0';
            $this->report['sums'][$nbCurrencyId]['left']      ??= '0';
            $this->report['sums'][$nbCurrencyId]['budgeted']  ??= '0';
        }
    }

    /**
     * Calculate the percentages for each budget. Part of the "budgets" block on the default report.
     */
    private function percentageReport(): void
    {
        // make percentages based on total amount.
        foreach ($this->report['budgets'] as $budgetId => $data) {
            foreach ($data['budget_limits'] as $limitId => $entry) {
                $budgetId                                                                      = (int) $budgetId;
                $limitId                                                                       = (int) $limitId;
                $currencyId                                                                    = (int) $entry['currency_id'];
                $spent                                                                         = $entry['spent'];
                $totalSpent                                                                    = $this->report['sums'][$currencyId]['spent'] ?? '0';
                $spentPct                                                                      = '0';
                $budgeted                                                                      = $entry['budgeted'];
                $totalBudgeted                                                                 = $this->report['sums'][$currencyId]['budgeted'] ?? '0';
                $budgetedPct                                                                   = '0';

                if (0 !== bccomp((string) $spent, '0') && 0 !== bccomp($totalSpent, '0')) {
                    $spentPct = round((float) bcmul(bcdiv((string) $spent, $totalSpent), '100'));
                }
                if (0 !== bccomp((string) $budgeted, '0') && 0 !== bccomp($totalBudgeted, '0')) {
                    $budgetedPct = round((float) bcmul(bcdiv((string) $budgeted, $totalBudgeted), '100'));
                }
                $this->report['sums'][$currencyId]['budgeted'] ??= '0';
                $this->report['budgets'][$budgetId]['budget_limits'][$limitId]['spent_pct']    = $spentPct;
                $this->report['budgets'][$budgetId]['budget_limits'][$limitId]['budgeted_pct'] = $budgetedPct;
            }
        }
    }

    public function getReport(): array
    {
        return $this->report;
    }

    public function setAccounts(Collection $accounts): void
    {
        $this->accounts = $accounts;
    }

    public function setBudgets(Collection $budgets): void
    {
        $this->budgets = $budgets;
    }

    public function setEnd(Carbon $end): void
    {
        $this->end = $end;
    }

    public function setStart(Carbon $start): void
    {
        $this->start = $start;
    }

    /**
     * @throws FireflyException
     */
    public function setUser(User $user): void
    {
        $this->repository->setUser($user);
        $this->blRepository->setUser($user);
        $this->opsRepository->setUser($user);
        $this->nbRepository->setUser($user);
        $this->currency = app('amount')->getNativeCurrencyByUserGroup($user->userGroup);
    }
}
