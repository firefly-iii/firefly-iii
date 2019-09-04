<?php
/**
 * DoubleController.php
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
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\OperationsRepositoryInterface;
use FireflyIII\Support\Http\Controllers\AugumentData;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Log;
use Throwable;

/**
 * Class DoubleController
 *
 */
class DoubleController extends Controller
{
    use AugumentData;

    /** @var AccountRepositoryInterface The account repository */
    protected $accountRepository;

    /** @var OperationsRepositoryInterface */
    private $opsRepository;

    /**
     * Constructor for ExpenseController
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                $this->accountRepository = app(AccountRepositoryInterface::class);
                $this->opsRepository     = app(OperationsRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Collection $accounts
     * @param Collection $doubles
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array|string
     */
    public function avgExpenses(Collection $accounts, Collection $doubles, Carbon $start, Carbon $end)
    {
        $expanded = $this->accountRepository->expandWithDoubles($doubles);
        $accounts = $accounts->merge($expanded);
        $spent    = $this->opsRepository->listExpenses($start, $end, $accounts);
        $result   = [];
        foreach ($spent as $currency) {
            foreach ($currency['transaction_journals'] as $journal) {
                $sourceId     = $journal['source_account_id'];
                $key          = sprintf('%d-%d', $sourceId, $currency['currency_id']);
                $result[$key] = $result[$key] ?? [
                        'transactions'            => 0,
                        'sum'                     => '0',
                        'avg'                     => '0',
                        'avg_float'               => 0,
                        'source_account_name'     => $journal['source_account_name'],
                        'source_account_id'       => $journal['source_account_id'],
                        'currency_id'             => $currency['currency_id'],
                        'currency_name'           => $currency['currency_name'],
                        'currency_symbol'         => $currency['currency_symbol'],
                        'currency_decimal_places' => $currency['currency_decimal_places'],
                    ];
                $result[$key]['transactions']++;
                $result[$key]['sum']       = bcadd($journal['amount'], $result[$key]['sum']);
                $result[$key]['avg']       = bcdiv($result[$key]['sum'], (string)$result[$key]['transactions']);
                $result[$key]['avg_float'] = (float)$result[$key]['avg'];
            }
        }
        // sort by amount_float
        // sort temp array by amount.
        $amounts = array_column($result, 'avg_float');
        array_multisort($amounts, SORT_ASC, $result);

        try {
            $result = view('reports.double.partials.avg-expenses', compact('result'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());
        }

        return $result;
    }

    /**
     * @param Collection $accounts
     * @param Collection $doubles
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array|string
     */
    public function avgIncome(Collection $accounts, Collection $doubles, Carbon $start, Carbon $end)
    {
        $expanded = $this->accountRepository->expandWithDoubles($doubles);
        $accounts = $accounts->merge($expanded);
        $spent    = $this->opsRepository->listIncome($start, $end, $accounts);
        $result   = [];
        foreach ($spent as $currency) {
            foreach ($currency['transaction_journals'] as $journal) {
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
        // sort by amount_float
        // sort temp array by amount.
        $amounts = array_column($result, 'avg_float');
        array_multisort($amounts, SORT_DESC, $result);

        try {
            $result = view('reports.double.partials.avg-income', compact('result'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());
        }

        return $result;
    }

    /**
     * @param Collection $accounts
     * @param Collection $double
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Factory|View
     */
    public function operations(Collection $accounts, Collection $double, Carbon $start, Carbon $end)
    {
        $withCounterpart = $this->accountRepository->expandWithDoubles($double);
        $together        = $accounts->merge($withCounterpart);
        $report          = [];
        $sums            = [];
        // see what happens when we collect transactions.
        $spent  = $this->opsRepository->listExpenses($start, $end, $together);
        $earned = $this->opsRepository->listIncome($start, $end, $together);
        // group and list per account name (as long as its not in accounts, only in double)

        /** @var array $currency */
        foreach ($spent as $currency) {
            $currencyId = $currency['currency_id'];

            $sums[$currencyId] = $sums[$currencyId] ?? [
                    'spent'                   => '0',
                    'earned'                  => '0',
                    'sum'                     => '0',
                    'currency_id'             => $currency['currency_id'],
                    'currency_name'           => $currency['currency_name'],
                    'currency_symbol'         => $currency['currency_symbol'],
                    'currency_code'           => $currency['currency_code'],
                    'currency_decimal_places' => $currency['currency_decimal_places'],
                ];

            /** @var array $journal */
            foreach ($currency['transaction_journals'] as $journal) {
                $destId              = $journal['destination_account_id'];
                $destName            = $journal['destination_account_name'];
                $destIban            = $journal['destination_account_iban'];
                $genericName         = $this->getCounterpartName($withCounterpart, $destId, $destName, $destIban);
                $objectName          = sprintf('%s (%s)', $genericName, $currency['currency_name']);
                $report[$objectName] = $report[$objectName] ?? [
                        'dest_name'               => '',
                        'dest_iban'               => '',
                        'source_name'             => '',
                        'source_iban'             => '',
                        'currency_id'             => $currency['currency_id'],
                        'currency_name'           => $currency['currency_name'],
                        'currency_symbol'         => $currency['currency_symbol'],
                        'currency_code'           => $currency['currency_code'],
                        'currency_decimal_places' => $currency['currency_decimal_places'],
                        'spent'                   => '0',
                        'earned'                  => '0',
                        'sum'                     => '0',
                    ];
                // set name
                $report[$objectName]['dest_name'] = $destName;
                $report[$objectName]['dest_iban'] = $destIban;

                // add amounts:
                $report[$objectName]['spent'] = bcadd($report[$objectName]['spent'], $journal['amount']);
                $report[$objectName]['sum']   = bcadd($report[$objectName]['sum'], $journal['amount']);
                $sums[$currencyId]['spent']   = bcadd($sums[$currencyId]['spent'], $journal['amount']);
                $sums[$currencyId]['sum']     = bcadd($sums[$currencyId]['sum'], $journal['amount']);
            }
        }

        /** @var array $currency */
        foreach ($earned as $currency) {
            $currencyId = $currency['currency_id'];

            $sums[$currencyId] = $sums[$currencyId] ?? [
                    'spent'                   => '0',
                    'earned'                  => '0',
                    'sum'                     => '0',
                    'currency_id'             => $currency['currency_id'],
                    'currency_name'           => $currency['currency_name'],
                    'currency_symbol'         => $currency['currency_symbol'],
                    'currency_code'           => $currency['currency_code'],
                    'currency_decimal_places' => $currency['currency_decimal_places'],
                ];

            /** @var array $journal */
            foreach ($currency['transaction_journals'] as $journal) {
                $sourceId            = $journal['source_account_id'];
                $sourceName          = $journal['source_account_name'];
                $sourceIban          = $journal['source_account_iban'];
                $genericName         = $this->getCounterpartName($withCounterpart, $sourceId, $sourceName, $sourceIban);
                $objectName          = sprintf('%s (%s)', $genericName, $currency['currency_name']);
                $report[$objectName] = $report[$objectName] ?? [
                        'dest_name'               => '',
                        'dest_iban'               => '',
                        'source_name'             => '',
                        'source_iban'             => '',
                        'currency_id'             => $currency['currency_id'],
                        'currency_name'           => $currency['currency_name'],
                        'currency_symbol'         => $currency['currency_symbol'],
                        'currency_code'           => $currency['currency_code'],
                        'currency_decimal_places' => $currency['currency_decimal_places'],
                        'spent'                   => '0',
                        'earned'                  => '0',
                        'sum'                     => '0',
                    ];

                // set name
                $report[$objectName]['source_name'] = $sourceName;
                $report[$objectName]['source_iban'] = $sourceIban;

                // add amounts:
                $report[$objectName]['earned'] = bcadd($report[$objectName]['earned'], $journal['amount']);
                $report[$objectName]['sum']    = bcadd($report[$objectName]['sum'], $journal['amount']);
                $sums[$currencyId]['earned']   = bcadd($sums[$currencyId]['earned'], $journal['amount']);
                $sums[$currencyId]['sum']      = bcadd($sums[$currencyId]['sum'], $journal['amount']);
            }
        }

        return view('reports.double.partials.accounts', compact('sums', 'report'));
    }

    /**
     * @param Collection $accounts
     * @param Collection $double
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Factory|View
     */
    public function operationsPerAsset(Collection $accounts, Collection $double, Carbon $start, Carbon $end)
    {
        $withCounterpart = $this->accountRepository->expandWithDoubles($double);
        $together        = $accounts->merge($withCounterpart);
        $report          = [];
        $sums            = [];
        // see what happens when we collect transactions.
        $spent  = $this->opsRepository->listExpenses($start, $end, $together);
        $earned = $this->opsRepository->listIncome($start, $end, $together);
        // group and list per account name (as long as its not in accounts, only in double)

        /** @var array $currency */
        foreach ($spent as $currency) {
            $currencyId = $currency['currency_id'];

            $sums[$currencyId] = $sums[$currencyId] ?? [
                    'spent'                   => '0',
                    'earned'                  => '0',
                    'sum'                     => '0',
                    'currency_id'             => $currency['currency_id'],
                    'currency_name'           => $currency['currency_name'],
                    'currency_symbol'         => $currency['currency_symbol'],
                    'currency_code'           => $currency['currency_code'],
                    'currency_decimal_places' => $currency['currency_decimal_places'],
                ];

            /** @var array $journal */
            foreach ($currency['transaction_journals'] as $journal) {
                $objectName          = sprintf('%s (%s)', $journal['source_account_name'], $currency['currency_name']);
                $report[$objectName] = $report[$objectName] ?? [
                        'account_id'              => $journal['source_account_id'],
                        'account_name'            => $objectName,
                        'currency_id'             => $currency['currency_id'],
                        'currency_name'           => $currency['currency_name'],
                        'currency_symbol'         => $currency['currency_symbol'],
                        'currency_code'           => $currency['currency_code'],
                        'currency_decimal_places' => $currency['currency_decimal_places'],
                        'spent'                   => '0',
                        'earned'                  => '0',
                        'sum'                     => '0',
                    ];
                // set name
                // add amounts:
                $report[$objectName]['spent'] = bcadd($report[$objectName]['spent'], $journal['amount']);
                $report[$objectName]['sum']   = bcadd($report[$objectName]['sum'], $journal['amount']);
                $sums[$currencyId]['spent']   = bcadd($sums[$currencyId]['spent'], $journal['amount']);
                $sums[$currencyId]['sum']     = bcadd($sums[$currencyId]['sum'], $journal['amount']);
            }
        }

        /** @var array $currency */
        foreach ($earned as $currency) {
            $currencyId = $currency['currency_id'];

            $sums[$currencyId] = $sums[$currencyId] ?? [
                    'spent'                   => '0',
                    'earned'                  => '0',
                    'sum'                     => '0',
                    'currency_id'             => $currency['currency_id'],
                    'currency_name'           => $currency['currency_name'],
                    'currency_symbol'         => $currency['currency_symbol'],
                    'currency_code'           => $currency['currency_code'],
                    'currency_decimal_places' => $currency['currency_decimal_places'],
                ];

            /** @var array $journal */
            foreach ($currency['transaction_journals'] as $journal) {
                $objectName          = sprintf('%s (%s)', $journal['destination_account_name'], $currency['currency_name']);
                $report[$objectName] = $report[$objectName] ?? [
                        'account_id'              => $journal['destination_account_id'],
                        'account_name'            => $objectName,
                        'currency_id'             => $currency['currency_id'],
                        'currency_name'           => $currency['currency_name'],
                        'currency_symbol'         => $currency['currency_symbol'],
                        'currency_code'           => $currency['currency_code'],
                        'currency_decimal_places' => $currency['currency_decimal_places'],
                        'spent'                   => '0',
                        'earned'                  => '0',
                        'sum'                     => '0',
                    ];

                // add amounts:
                $report[$objectName]['earned'] = bcadd($report[$objectName]['earned'], $journal['amount']);
                $report[$objectName]['sum']    = bcadd($report[$objectName]['sum'], $journal['amount']);
                $sums[$currencyId]['earned']   = bcadd($sums[$currencyId]['earned'], $journal['amount']);
                $sums[$currencyId]['sum']      = bcadd($sums[$currencyId]['sum'], $journal['amount']);
            }
        }

        return view('reports.double.partials.accounts-per-asset', compact('sums', 'report'));
    }

    /**
     * @param Collection $accounts
     * @param Collection $doubles
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array|string
     */
    public function topExpenses(Collection $accounts, Collection $doubles, Carbon $start, Carbon $end)
    {
        $expanded = $this->accountRepository->expandWithDoubles($doubles);
        $accounts = $accounts->merge($expanded);
        $spent    = $this->opsRepository->listExpenses($start, $end, $accounts);
        $result   = [];
        foreach ($spent as $currency) {
            foreach ($currency['transaction_journals'] as $journal) {
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
                    'source_account_name'      => $journal['source_account_name'],
                    'source_account_id'        => $journal['source_account_id'],
                ];
            }
        }
        // sort by amount_float
        // sort temp array by amount.
        $amounts = array_column($result, 'amount_float');
        array_multisort($amounts, SORT_ASC, $result);

        try {
            $result = view('reports.double.partials.top-expenses', compact('result'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());
        }

        return $result;
    }

    /**
     * @param Collection $accounts
     * @param Collection $doubles
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array|string
     */
    public function topIncome(Collection $accounts, Collection $doubles, Carbon $start, Carbon $end)
    {
        $expanded = $this->accountRepository->expandWithDoubles($doubles);
        $accounts = $accounts->merge($expanded);
        $spent    = $this->opsRepository->listIncome($start, $end, $accounts);
        $result   = [];
        foreach ($spent as $currency) {
            foreach ($currency['transaction_journals'] as $journal) {
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
                    'source_account_name'      => $journal['source_account_name'],
                    'source_account_id'        => $journal['source_account_id'],
                ];
            }
        }
        // sort by amount_float
        // sort temp array by amount.
        $amounts = array_column($result, 'amount_float');
        array_multisort($amounts, SORT_DESC, $result);

        try {
            $result = view('reports.double.partials.top-income', compact('result'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());
        }

        return $result;
    }


    //
    //
    //    /**
    //     * Generates the overview per budget.
    //     *
    //     * @param Collection $accounts
    //     * @param Collection $expense
    //     * @param Carbon $start
    //     * @param Carbon $end
    //     *
    //     * @return string
    //     */
    //    public function budget(Collection $accounts, Collection $expense, Carbon $start, Carbon $end): string
    //    {
    //        // Properties for cache:
    //        $cache = new CacheProperties;
    //        $cache->addProperty($start);
    //        $cache->addProperty($end);
    //        $cache->addProperty('expense-budget');
    //        $cache->addProperty($accounts->pluck('id')->toArray());
    //        $cache->addProperty($expense->pluck('id')->toArray());
    //        if ($cache->has()) {
    //            return $cache->get(); // @codeCoverageIgnore
    //        }
    //        $combined = $this->combineAccounts($expense);
    //        $all      = new Collection;
    //        foreach ($combined as $combi) {
    //            $all = $all->merge($combi);
    //        }
    //        // now find spent / earned:
    //        $spent = $this->spentByBudget($accounts, $all, $start, $end);
    //        // join arrays somehow:
    //        $together = [];
    //        foreach ($spent as $categoryId => $spentInfo) {
    //            if (!isset($together[$categoryId])) {
    //                $together[$categoryId]['spent']       = $spentInfo;
    //                $together[$categoryId]['budget']      = $spentInfo['name'];
    //                $together[$categoryId]['grand_total'] = '0';
    //            }
    //            $together[$categoryId]['grand_total'] = bcadd($spentInfo['grand_total'], $together[$categoryId]['grand_total']);
    //        }
    //        try {
    //            $result = view('reports.partials.exp-budgets', compact('together'))->render();
    //            // @codeCoverageIgnoreStart
    //        } catch (Throwable $e) {
    //            Log::error(sprintf('Could not render category::budget: %s', $e->getMessage()));
    //            $result = sprintf('An error prevented Firefly III from rendering: %s. Apologies.', $e->getMessage());
    //        }
    //        // @codeCoverageIgnoreEnd
    //        $cache->store($result);
    //
    //        return $result;
    //    }
    //
    //
    //
    //    /**
    //     * Generates the overview per category (spent and earned).
    //     *
    //     * @param Collection $accounts
    //     * @param Collection $expense
    //     * @param Carbon $start
    //     * @param Carbon $end
    //     *
    //     * @return string
    //     */
    //    public function category(Collection $accounts, Collection $expense, Carbon $start, Carbon $end): string
    //    {
    //        // Properties for cache:
    //        $cache = new CacheProperties;
    //        $cache->addProperty($start);
    //        $cache->addProperty($end);
    //        $cache->addProperty('expense-category');
    //        $cache->addProperty($accounts->pluck('id')->toArray());
    //        $cache->addProperty($expense->pluck('id')->toArray());
    //        if ($cache->has()) {
    //            return $cache->get(); // @codeCoverageIgnore
    //        }
    //        $combined = $this->combineAccounts($expense);
    //        $all      = new Collection;
    //        foreach ($combined as $combi) {
    //            $all = $all->merge($combi);
    //        }
    //        // now find spent / earned:
    //        $spent  = $this->spentByCategory($accounts, $all, $start, $end);
    //        $earned = $this->earnedByCategory($accounts, $all, $start, $end);
    //        // join arrays somehow:
    //        $together = [];
    //        foreach ($spent as $categoryId => $spentInfo) {
    //            if (!isset($together[$categoryId])) {
    //                $together[$categoryId]['spent']       = $spentInfo;
    //                $together[$categoryId]['category']    = $spentInfo['name'];
    //                $together[$categoryId]['grand_total'] = '0';
    //            }
    //            $together[$categoryId]['grand_total'] = bcadd($spentInfo['grand_total'], $together[$categoryId]['grand_total']);
    //        }
    //        foreach ($earned as $categoryId => $earnedInfo) {
    //            if (!isset($together[$categoryId])) {
    //                $together[$categoryId]['earned']      = $earnedInfo;
    //                $together[$categoryId]['category']    = $earnedInfo['name'];
    //                $together[$categoryId]['grand_total'] = '0';
    //            }
    //            $together[$categoryId]['grand_total'] = bcadd($earnedInfo['grand_total'], $together[$categoryId]['grand_total']);
    //        }
    //        try {
    //            $result = view('reports.partials.exp-categories', compact('together'))->render();
    //            // @codeCoverageIgnoreStart
    //        } catch (Throwable $e) {
    //            Log::error(sprintf('Could not render category::expenses: %s', $e->getMessage()));
    //            $result = sprintf('An error prevented Firefly III from rendering: %s. Apologies.', $e->getMessage());
    //        }
    //        // @codeCoverageIgnoreEnd
    //        $cache->store($result);
    //
    //        return $result;
    //    }
    //
    //
    //    /**
    //     * Overview of spending.
    //     *
    //     * @param Collection $accounts
    //     * @param Collection $expense
    //     * @param Carbon $start
    //     * @param Carbon $end
    //     *
    //     * @return array|mixed|string
    //     */
    //    public function spent(Collection $accounts, Collection $expense, Carbon $start, Carbon $end)
    //    {
    //        // chart properties for cache:
    //        $cache = new CacheProperties;
    //        $cache->addProperty($start);
    //        $cache->addProperty($end);
    //        $cache->addProperty('expense-spent');
    //        $cache->addProperty($accounts->pluck('id')->toArray());
    //        $cache->addProperty($expense->pluck('id')->toArray());
    //        if ($cache->has()) {
    //            return $cache->get(); // @codeCoverageIgnore
    //        }
    //
    //        $combined = $this->combineAccounts($expense);
    //        $result   = [];
    //
    //        foreach ($combined as $name => $combi) {
    //            /**
    //             * @var string
    //             * @var Collection $combi
    //             */
    //            $spent         = $this->spentInPeriod($accounts, $combi, $start, $end);
    //            $earned        = $this->earnedInPeriod($accounts, $combi, $start, $end);
    //            $result[$name] = [
    //                'spent'  => $spent,
    //                'earned' => $earned,
    //            ];
    //        }
    //        try {
    //            $result = view('reports.partials.exp-not-grouped', compact('result'))->render();
    //            // @codeCoverageIgnoreStart
    //        } catch (Throwable $e) {
    //            Log::error(sprintf('Could not render category::expenses: %s', $e->getMessage()));
    //            $result = sprintf('An error prevented Firefly III from rendering: %s. Apologies.', $e->getMessage());
    //        }
    //        // @codeCoverageIgnoreEnd
    //        $cache->store($result);
    //
    //        return $result;
    //        // for period, get spent and earned for each account (by name)
    //    }
    //
    //
    //    /**
    //     * List of top expenses.
    //     *
    //     * @param Collection $accounts
    //     * @param Collection $expense
    //     * @param Carbon $start
    //     * @param Carbon $end
    //     *
    //     * @return string
    //     */
    //    public function topExpense(Collection $accounts, Collection $expense, Carbon $start, Carbon $end): string
    //    {
    //        // Properties for cache:
    //        $cache = new CacheProperties;
    //        $cache->addProperty($start);
    //        $cache->addProperty($end);
    //        $cache->addProperty('top-expense');
    //        $cache->addProperty($accounts->pluck('id')->toArray());
    //        $cache->addProperty($expense->pluck('id')->toArray());
    //        if ($cache->has()) {
    //            return $cache->get(); // @codeCoverageIgnore
    //        }
    //        $combined = $this->combineAccounts($expense);
    //        $all      = new Collection;
    //        foreach ($combined as $combi) {
    //            $all = $all->merge($combi);
    //        }
    //        // get all expenses in period:
    //        /** @var GroupCollectorInterface $collector */
    //        $collector = app(GroupCollectorInterface::class);
    //
    //        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setAccounts($accounts);
    //        $collector->setAccounts($all)->withAccountInformation();
    //        $sorted = $collector->getExtractedJournals();
    //
    //        usort($sorted, function ($a, $b) {
    //            return $a['amount'] <=> $b['amount']; // @codeCoverageIgnore
    //        });
    //
    //        try {
    //            $result = view('reports.partials.top-transactions', compact('sorted'))->render();
    //            // @codeCoverageIgnoreStart
    //        } catch (Throwable $e) {
    //            Log::error(sprintf('Could not render category::topExpense: %s', $e->getMessage()));
    //            $result = sprintf('An error prevented Firefly III from rendering: %s. Apologies.', $e->getMessage());
    //        }
    //        // @codeCoverageIgnoreEnd
    //        $cache->store($result);
    //
    //        return $result;
    //    }
    //
    //    /**
    //     * List of top income.
    //     *
    //     * @param Collection $accounts
    //     * @param Collection $expense
    //     * @param Carbon $start
    //     * @param Carbon $end
    //     *
    //     * @return mixed|string
    //     */
    //    public function topIncome(Collection $accounts, Collection $expense, Carbon $start, Carbon $end)
    //    {
    //        // Properties for cache:
    //        $cache = new CacheProperties;
    //        $cache->addProperty($start);
    //        $cache->addProperty($end);
    //        $cache->addProperty('top-income');
    //        $cache->addProperty($accounts->pluck('id')->toArray());
    //        $cache->addProperty($expense->pluck('id')->toArray());
    //        if ($cache->has()) {
    //            return $cache->get(); // @codeCoverageIgnore
    //        }
    //        $combined = $this->combineAccounts($expense);
    //        $all      = new Collection;
    //        foreach ($combined as $combi) {
    //            $all = $all->merge($combi);
    //        }
    //        // get all expenses in period:
    //
    //        /** @var GroupCollectorInterface $collector */
    //        $collector = app(GroupCollectorInterface::class);
    //
    //        $total = $accounts->merge($all);
    //        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->setAccounts($total)->withAccountInformation();
    //        $sorted = $collector->getExtractedJournals();
    //
    //        foreach (array_keys($sorted) as $key) {
    //            $sorted[$key]['amount'] = bcmul($sorted[$key]['amount'], '-1');
    //        }
    //
    //        usort($sorted, function ($a, $b) {
    //            return $a['amount'] <=> $b['amount']; // @codeCoverageIgnore
    //        });
    //
    //        try {
    //            $result = view('reports.partials.top-transactions', compact('sorted'))->render();
    //            // @codeCoverageIgnoreStart
    //        } catch (Throwable $e) {
    //            Log::error(sprintf('Could not render category::topIncome: %s', $e->getMessage()));
    //            $result = sprintf('An error prevented Firefly III from rendering: %s. Apologies.', $e->getMessage());
    //        }
    //        // @codeCoverageIgnoreEnd
    //        $cache->store($result);
    //
    //        return $result;
    //    }

    /**
     * TODO this method is double.
     *
     * @param Collection $accounts
     * @param int        $id
     * @param string     $name
     * @param string     $iban
     *
     * @return string
     */
    private function getCounterpartName(Collection $accounts, int $id, string $name, string $iban): string
    {
        /** @var Account $account */
        foreach ($accounts as $account) {
            if ($account->name === $name && $account->id !== $id) {
                return $account->name;
            }
            if ($account->iban === $iban && $account->id !== $id) {
                return $account->iban;
            }
        }

        return $name;
    }
}
