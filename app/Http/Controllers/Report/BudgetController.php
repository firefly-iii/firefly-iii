<?php
/**
 * BudgetController.php
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
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Report\BudgetReportHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\BasicDataSupport;
use Illuminate\Support\Collection;
use Log;
use Throwable;

/**
 * Class BudgetController.
 */
class BudgetController extends Controller
{
    use BasicDataSupport;

    /**
     * @param Collection $accounts
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     */
    public function accountPerBudget(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end)
    {
        // get all journals.
        $opsRepository = app(OperationsRepositoryInterface::class);
        $spent         = $opsRepository->listExpenses($start, $end, $accounts, $budgets);
        $report        = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountId          = $account->id;
            $report[$accountId] = $report[$accountId] ?? [
                    'name'       => $account->name,
                    'id'         => $account->id,
                    'iban'       => $account->iban,
                    'currencies' => [],
                ];
        }

        // loop expenses.
        foreach ($spent as $currency) {
            $currencyId = $currency['currency_id'];

            foreach ($currency['budgets'] as $budget) {
                foreach ($budget['transaction_journals'] as $journal) {
                    $sourceAccountId = $journal['source_account_id'];


                    $report[$sourceAccountId]['currencies'][$currencyId]                           = $report[$sourceAccountId]['currencies'][$currencyId] ?? [
                            'currency_id'             => $currency['currency_id'],
                            'currency_symbol'         => $currency['currency_symbol'],
                            'currency_name'           => $currency['currency_name'],
                            'currency_decimal_places' => $currency['currency_decimal_places'],
                            'budgets'                 => [],
                        ];
                    $report[$sourceAccountId]['currencies'][$currencyId]['budgets'][$budget['id']]
                                                                                                   = $report[$sourceAccountId]['currencies'][$currencyId]['budgets'][$budget['id']]
                                                                                                     ?? '0';
                    $report[$sourceAccountId]['currencies'][$currencyId]['budgets'][$budget['id']] = bcadd(
                        $report[$sourceAccountId]['currencies'][$currencyId]['budgets'][$budget['id']], $journal['amount']
                    );
                }
            }
        }

        return view('reports.budget.partials.account-per-budget', compact('report', 'budgets'));
    }

    /**
     * @param Collection $accounts
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     */
    public function accounts(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end)
    {
        // get all journals.
        $opsRepository = app(OperationsRepositoryInterface::class);
        $spent         = $opsRepository->listExpenses($start, $end, $accounts, $budgets);
        $report        = [];
        $sums          = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountId          = $account->id;
            $report[$accountId] = $report[$accountId] ?? [
                    'name'       => $account->name,
                    'id'         => $account->id,
                    'iban'       => $account->iban,
                    'currencies' => [],
                ];
        }

        // loop expenses.
        foreach ($spent as $currency) {
            $currencyId        = $currency['currency_id'];
            $sums[$currencyId] = $sums[$currencyId] ?? [
                    'currency_id'             => $currency['currency_id'],
                    'currency_symbol'         => $currency['currency_symbol'],
                    'currency_name'           => $currency['currency_name'],
                    'currency_decimal_places' => $currency['currency_decimal_places'],
                    'sum'                     => '0',
                ];
            foreach ($currency['budgets'] as $budget) {
                foreach ($budget['transaction_journals'] as $journal) {
                    $sourceAccountId                                            = $journal['source_account_id'];
                    $report[$sourceAccountId]['currencies'][$currencyId]        = $report[$sourceAccountId]['currencies'][$currencyId] ?? [
                            'currency_id'             => $currency['currency_id'],
                            'currency_symbol'         => $currency['currency_symbol'],
                            'currency_name'           => $currency['currency_name'],
                            'currency_decimal_places' => $currency['currency_decimal_places'],
                            'sum'                     => '0',
                        ];
                    $report[$sourceAccountId]['currencies'][$currencyId]['sum'] = bcadd(
                        $report[$sourceAccountId]['currencies'][$currencyId]['sum'], $journal['amount']
                    );
                    $sums[$currencyId]['sum']                                   = bcadd($sums[$currencyId]['sum'], $journal['amount']);
                }
            }
        }

        return view('reports.budget.partials.accounts', compact('sums', 'report'));
    }

    /**
     * @param Collection $accounts
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     */
    public function avgExpenses(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end)
    {
        // get all journals.
        $opsRepository = app(OperationsRepositoryInterface::class);
        $spent         = $opsRepository->listExpenses($start, $end, $accounts, $budgets);
        $result        = [];
        foreach ($spent as $currency) {
            $currencyId = $currency['currency_id'];
            foreach ($currency['budgets'] as $budget) {
                foreach ($budget['transaction_journals'] as $journal) {
                    $destinationId          = $journal['destination_account_id'];
                    $result[$destinationId] = $result[$destinationId] ?? [
                            'transactions'             => 0,
                            'sum'                      => '0',
                            'avg'                      => '0',
                            'avg_float'                => 0,
                            'destination_account_name' => $journal['destination_account_name'],
                            'destination_account_id'   => $journal['destination_account_id'],
                            'currency_id'              => $currency['currency_id'],
                            'currency_name'            => $currency['currency_name'],
                            'currency_symbol'          => $currency['currency_symbol'],
                            'currency_decimal_places'  => $currency['currency_decimal_places'],
                        ];
                    $result[$destinationId]['transactions']++;
                    $result[$destinationId]['sum']       = bcadd($journal['amount'], $result[$destinationId]['sum']);
                    $result[$destinationId]['avg']       = bcdiv($result[$destinationId]['sum'], (string)$result[$destinationId]['transactions']);
                    $result[$destinationId]['avg_float'] = (float)$result[$destinationId]['avg'];
                }
            }
        }
        // sort by amount_float
        // sort temp array by amount.
        $amounts = array_column($result, 'avg_float');
        array_multisort($amounts, SORT_ASC, $result);

        try {
            $result = view('reports.budget.partials.avg-expenses', compact('result'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());
        }

        return $result;
    }

    /**
     * @param Collection $accounts
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function budgets(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end)
    {
        // get all journals.
        $opsRepository = app(OperationsRepositoryInterface::class);
        $spent         = $opsRepository->listExpenses($start, $end, $accounts, $budgets);
        $sums          = [];
        $report        = [];
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $budgetId          = $budget->id;
            $report[$budgetId] = $report[$budgetId] ?? [
                    'name'       => $budget->name,
                    'id'         => $budget->id,
                    'currencies' => [],
                ];
        }
        foreach ($spent as $currency) {
            $currencyId        = $currency['currency_id'];
            $sums[$currencyId] = $sums[$currencyId] ?? [
                    'currency_id'             => $currency['currency_id'],
                    'currency_symbol'         => $currency['currency_symbol'],
                    'currency_name'           => $currency['currency_name'],
                    'currency_decimal_places' => $currency['currency_decimal_places'],
                    'sum'                     => '0',
                ];
            /** @var array $budget */
            foreach ($currency['budgets'] as $budget) {
                $budgetId = $budget['id'];

                foreach ($budget['transaction_journals'] as $journal) {
                    // add currency info to report array:
                    $report[$budgetId]['currencies'][$currencyId]        = $report[$budgetId]['currencies'][$currencyId] ?? [
                            'sum'                     => '0',
                            'currency_id'             => $currency['currency_id'],
                            'currency_symbol'         => $currency['currency_symbol'],
                            'currency_name'           => $currency['currency_name'],
                            'currency_decimal_places' => $currency['currency_decimal_places'],
                        ];
                    $report[$budgetId]['currencies'][$currencyId]['sum'] = bcadd($report[$budgetId]['currencies'][$currencyId]['sum'], $journal['amount']);
                    $sums[$currencyId]['sum']                            = bcadd($sums[$currencyId]['sum'], $journal['amount']);
                }
            }
        }

        return view('reports.budget.partials.budgets', compact('sums', 'report'));
    }

    /**
     * Show partial overview of budgets.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return mixed|string
     */
    public function general(Collection $accounts, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('budget-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $helper  = app(BudgetReportHelperInterface::class);
        $budgets = $helper->getBudgetReport($start, $end, $accounts);
        try {
            $result = view('reports.partials.budgets', compact('budgets'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render reports.partials.budgets: %s', $e->getMessage()));
            $result = 'Could not render view.';
        }
        // @codeCoverageIgnoreEnd
        $cache->store($result);

        return $result;
    }

    /**
     * Show budget overview for a period.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return mixed|string
     */
    public function period(Collection $accounts, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('budget-period-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        /** @var OperationsRepositoryInterface $opsRepository */
        $opsRepository = app(OperationsRepositoryInterface::class);
        $periods       = app('navigation')->listOfPeriods($start, $end);
        $keyFormat     = app('navigation')->preferredCarbonFormat($start, $end);


        // list expenses for budgets in account(s)
        $expenses = $opsRepository->listExpenses($start, $end, $accounts);

        $report = [];
        foreach ($expenses as $currency) {
            foreach ($currency['budgets'] as $budget) {
                foreach ($budget['transaction_journals'] as $journal) {
                    $key                                = sprintf('%d-%d', $budget['id'], $currency['currency_id']);
                    $dateKey                            = $journal['date']->format($keyFormat);
                    $report[$key]                       = $report[$key] ?? [
                            'id'                      => $budget['id'],
                            'name'                    => sprintf('%s (%s)', $budget['name'], $currency['currency_name']),
                            'sum'                     => '0',
                            'currency_id'             => $currency['currency_id'],
                            'currency_name'           => $currency['currency_name'],
                            'currency_symbol'         => $currency['currency_symbol'],
                            'currency_code'           => $currency['currency_code'],
                            'currency_decimal_places' => $currency['currency_decimal_places'],
                            'entries'                 => [],
                        ];
                    $report[$key] ['entries'][$dateKey] = $report[$key] ['entries'][$dateKey] ?? '0';
                    $report[$key] ['entries'][$dateKey] = bcadd($journal['amount'], $report[$key] ['entries'][$dateKey]);
                    $report[$key] ['sum']               = bcadd($report[$key] ['sum'], $journal['amount']);
                }
            }
        }
        try {
            $result = view('reports.partials.budget-period', compact('report', 'periods'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = 'Could not render view.';
        }
        // @codeCoverageIgnoreEnd
        $cache->store($result);

        return $result;
    }

    /**
     * @param Collection $accounts
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     */
    public function topExpenses(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end)
    {
        // get all journals.
        $opsRepository = app(OperationsRepositoryInterface::class);
        $spent         = $opsRepository->listExpenses($start, $end, $accounts, $budgets);
        $result        = [];
        foreach ($spent as $currency) {
            $currencyId = $currency['currency_id'];
            foreach ($currency['budgets'] as $budget) {
                foreach ($budget['transaction_journals'] as $journal) {
                    $result[] = [
                        'description'              => $journal['description'],
                        'transaction_group_id'     => $journal['transaction_group_id'],
                        'amount_float'             => (float)$journal['amount'],
                        'amount'                   => $journal['amount'],
                        'date'                     => $journal['date']->formatLocalized($this->monthAndDayFormat),
                        'destination_account_name' => $journal['destination_account_name'],
                        'destination_account_id'   => $journal['destination_account_id'],
                        'currency_id'              => $currency['currency_id'],
                        'currency_name'            => $currency['currency_name'],
                        'currency_symbol'          => $currency['currency_symbol'],
                        'currency_decimal_places'  => $currency['currency_decimal_places'],
                    ];
                }
            }
        }
        // sort by amount_float
        // sort temp array by amount.
        $amounts = array_column($result, 'amount_float');
        array_multisort($amounts, SORT_ASC, $result);

        try {
            $result = view('reports.budget.partials.top-expenses', compact('result'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());
        }

        return $result;
    }

}
