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
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\NoBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\BasicDataSupport;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Log;
use Throwable;

/**
 * Class BudgetController.
 */
class BudgetController extends Controller
{
    use BasicDataSupport;

    /** @var BudgetLimitRepositoryInterface */
    private $blRepository;
    /** @var NoBudgetRepositoryInterface */
    private $nbRepository;
    /** @var OperationsRepositoryInterface */
    private $opsRepository;
    /** @var BudgetRepositoryInterface */
    private $repository;

    /**
     * ExpenseReportController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->opsRepository = app(OperationsRepositoryInterface::class);
                $this->repository    = app(BudgetRepositoryInterface::class);
                $this->blRepository  = app(BudgetLimitRepositoryInterface::class);
                $this->nbRepository  = app(NoBudgetRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Collection $accounts
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Factory|View
     */
    public function accountPerBudget(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end)
    {
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $budgets);
        $report = [];
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
     *
     * @return Factory|View
     */
    public function accounts(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end)
    {
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $budgets);
        $report = [];
        $sums   = [];
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
     *
     * @return array|string
     */
    public function avgExpenses(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end)
    {
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $budgets);
        $result = [];
        foreach ($spent as $currency) {
            $currencyId = $currency['currency_id'];
            foreach ($currency['budgets'] as $budget) {
                foreach ($budget['transaction_journals'] as $journal) {
                    $destinationId = $journal['destination_account_id'];
                    $key           = sprintf('%d-%d', $destinationId, $currency['currency_id']);
                    $result[$key]  = $result[$key] ?? [
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
                    $result[$key]['transactions']++;
                    $result[$key]['sum']       = bcadd($journal['amount'], $result[$key]['sum']);
                    $result[$key]['avg']       = bcdiv($result[$key]['sum'], (string)$result[$key]['transactions']);
                    $result[$key]['avg_float'] = (float)$result[$key]['avg'];
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
     * @return Factory|View
     */
    public function budgets(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end)
    {
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $budgets);
        $sums   = [];
        $report = [];
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
                            'sum_pct'                 => '0',
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

        // loop again to get percentages.
        foreach ($report as $budgetId => $data) {
            foreach ($data['currencies'] as $currencyId => $data) {
                $sum   = $data['sum'] ?? '0';
                $total = $sums[$currencyId]['sum'] ?? '0';
                $pct   = '0';
                if (0 !== bccomp($sum, '0') && 0 !== bccomp($total, '9')) {
                    $pct = round(bcmul(bcdiv($sum, $total), '100'));

                }
                $report[$budgetId]['currencies'][$currencyId]['sum_pct'] = $pct;
            }
        }

        return view('reports.budget.partials.budgets', compact('sums', 'report'));
    }

    /**
     * Show partial overview of budgets.
     * TODO can be replaced I think.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return mixed|string
     */
    public function general(Collection $accounts, Carbon $start, Carbon $end)
    {
        $report          = [
            'budgets' => [],
            'sums'    => [],
        ];
        $budgets         = $this->repository->getBudgets();
        $defaultCurrency = app('amount')->getDefaultCurrency();
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $budgetId                     = $budget->id;
            $report['budgets'][$budgetId] = $report['budgets'][$budgetId] ?? [
                    'budget_id'     => $budgetId,
                    'budget_name'   => $budget->name,
                    'no_budget'     => false,
                    'budget_limits' => [],
                ];

            // get all budget limits for budget in period:
            $limits = $this->blRepository->getBudgetLimits($budget, $start, $end);
            /** @var BudgetLimit $limit */
            foreach ($limits as $limit) {
                $limitId   = $limit->id;
                $currency  = $limit->transactionCurrency ?? $defaultCurrency;
                $expenses  = $this->opsRepository->sumExpenses($limit->start_date, $limit->end_date, $accounts, new Collection([$budget]));
                $spent     = $expenses[$currency->id]['sum'] ?? '0';
                $left      = -1 === bccomp(bcadd($limit->amount, $spent), '0') ? '0' : bcadd($limit->amount, $spent);
                $overspent = 1 === bccomp(bcmul($spent, '-1'), $limit->amount) ? bcadd($spent, $limit->amount) : '0';

                $report['budgets'][$budgetId]['budget_limits'][$limitId] = $report['budgets'][$budgetId]['budget_limits'][$limitId] ?? [
                        'budget_limit_id'         => $limitId,
                        'start_date'              => $limit->start_date,
                        'end_date'                => $limit->end_date,
                        'budgeted'                => $limit->amount,
                        'budgeted_pct'            => '0',
                        'spent'                   => $spent,
                        'spent_pct'               => '0',
                        'left'                    => $left,
                        'overspent'               => $overspent,
                        'currency_id'             => $currency->id,
                        'currency_code'           => $currency->code,
                        'currency_name'           => $currency->name,
                        'currency_symbol'         => $currency->symbol,
                        'currency_decimal_places' => $currency->decimal_places,
                    ];

                // make sum information:
                $report['sums'][$currency->id]              = $report['sums'][$currency->id] ?? [
                        'budgeted'                => '0',
                        'spent'                   => '0',
                        'left'                    => '0',
                        'overspent'               => '0',
                        'currency_id'             => $currency->id,
                        'currency_code'           => $currency->code,
                        'currency_name'           => $currency->name,
                        'currency_symbol'         => $currency->symbol,
                        'currency_decimal_places' => $currency->decimal_places,
                    ];
                $report['sums'][$currency->id]['budgeted']  = bcadd($report['sums'][$currency->id]['budgeted'], $limit->amount);
                $report['sums'][$currency->id]['spent']     = bcadd($report['sums'][$currency->id]['spent'], $spent);
                $report['sums'][$currency->id]['left']      = bcadd($report['sums'][$currency->id]['left'], bcadd($limit->amount, $spent));
                $report['sums'][$currency->id]['overspent'] = bcadd($report['sums'][$currency->id]['overspent'], $overspent);
            }
        }

        // add no budget info.
        $report['budgets'][0] = $report['budgets'][0] ?? [
                'budget_id'     => null,
                'budget_name'   => null,
                'no_budget'     => true,
                'budget_limits' => [],
            ];
        $noBudget             = $this->nbRepository->sumExpenses($start, $end);
        foreach ($noBudget as $noBudgetEntry) {
            $report['budgets'][0]['budget_limits'][] = [
                'budget_limit_id'         => null,
                'start_date'              => $start,
                'end_date'                => $end,
                'budgeted'                => '0',
                'budgeted_pct'            => '0',
                'spent'                   => $noBudgetEntry['sum'],
                'spent_pct'               => '0',
                'left'                    => '0',
                'overspent'               => '0',
                'currency_id'             => $noBudgetEntry['currency_id'],
                'currency_code'           => $noBudgetEntry['currency_code'],
                'currency_name'           => $noBudgetEntry['currency_name'],
                'currency_symbol'         => $noBudgetEntry['currency_symbol'],
                'currency_decimal_places' => $noBudgetEntry['currency_decimal_places'],
            ];
            $report['sums'][$noBudgetEntry['currency_id']]['spent']
                                                     = bcadd($report['sums'][$noBudgetEntry['currency_id']]['spent'] ?? '0', $noBudgetEntry['sum']);
        }

        // make percentages based on total amount.
        foreach ($report['budgets'] as $budgetId => $data) {
            foreach ($data['budget_limits'] as $limitId => $entry) {
                $currencyId = $entry['currency_id'];

                $spent      = $entry['spent'];
                $totalSpent = $report['sums'][$currencyId]['spent'] ?? '0';
                $spentPct   = '0';

                $budgeted      = $entry['budgeted'];
                $totalBudgeted = $report['sums'][$currencyId]['budgeted'] ?? '0';;
                $budgetedPct = '0';

                if (0 !== bccomp($spent, '0') && 0 !== bccomp($totalSpent, '0')) {
                    $spentPct = round(bcmul(bcdiv($spent, $totalSpent), '100'));
                }
                if (0 !== bccomp($budgeted, '0') && 0 !== bccomp($totalBudgeted, '0')) {
                    $budgetedPct = round(bcmul(bcdiv($budgeted, $totalBudgeted), '100'));
                }
                $report['budgets'][$budgetId]['budget_limits'][$limitId]['spent_pct']    = $spentPct;
                $report['budgets'][$budgetId]['budget_limits'][$limitId]['budgeted_pct'] = $budgetedPct;
            }
        }

        //        var_dump($noBudget);
        //
        //
        //        echo '<pre>';
        //        print_r($report);
        //        exit;
        //        try {
        $result = view('reports.partials.budgets', compact('report'))->render();
        // @codeCoverageIgnoreStart
        //        } catch (Throwable $e) {
        //            Log::debug(sprintf('Could not render reports.partials.budgets: %s', $e->getMessage()));
        //            $result = 'Could not render view.';
        //        }

        // @codeCoverageIgnoreEnd

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

        $periods   = app('navigation')->listOfPeriods($start, $end);
        $keyFormat = app('navigation')->preferredCarbonFormat($start, $end);


        // list expenses for budgets in account(s)
        $expenses = $this->opsRepository->listExpenses($start, $end, $accounts);

        $report = [];
        foreach ($expenses as $currency) {
            foreach ($currency['budgets'] as $budget) {
                $count = 0;
                foreach ($budget['transaction_journals'] as $journal) {
                    $count++;
                    $key                               = sprintf('%d-%d', $budget['id'], $currency['currency_id']);
                    $dateKey                           = $journal['date']->format($keyFormat);
                    $report[$key]                      = $report[$key] ?? [
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
                    $report[$key]['entries'][$dateKey] = $report[$key] ['entries'][$dateKey] ?? '0';
                    $report[$key]['entries'][$dateKey] = bcadd($journal['amount'], $report[$key] ['entries'][$dateKey]);
                    $report[$key]['sum']               = bcadd($report[$key] ['sum'], $journal['amount']);
                    $report[$key]['avg']               = bcdiv($report[$key]['sum'], (string)$count);
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
     *
     * @return array|string
     */
    public function topExpenses(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end)
    {
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $budgets);
        $result = [];
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
                        'budget_id'                => $budget['id'],
                        'budget_name'              => $budget['name'],
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
