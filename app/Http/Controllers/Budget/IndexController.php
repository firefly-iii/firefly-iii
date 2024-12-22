<?php

/**
 * IndexController.php
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

namespace FireflyIII\Http\Controllers\Budget;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    use DateCalculation;

    private AvailableBudgetRepositoryInterface $abRepository;
    private BudgetLimitRepositoryInterface     $blRepository;
    private CurrencyRepositoryInterface        $currencyRepository;
    private OperationsRepositoryInterface      $opsRepository;
    private BudgetRepositoryInterface          $repository;

    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.budgets'));
                app('view')->share('mainTitleIcon', 'fa-pie-chart');
                $this->repository         = app(BudgetRepositoryInterface::class);
                $this->opsRepository      = app(OperationsRepositoryInterface::class);
                $this->abRepository       = app(AvailableBudgetRepositoryInterface::class);
                $this->currencyRepository = app(CurrencyRepositoryInterface::class);
                $this->blRepository       = app(BudgetLimitRepositoryInterface::class);
                $this->repository->cleanupBudgets();

                return $next($request);
            }
        );
    }

    /**
     * Show all budgets.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     *                                              */
    public function index(?Carbon $start = null, ?Carbon $end = null)
    {
        $this->abRepository->cleanup();
        app('log')->debug(sprintf('Start of IndexController::index("%s", "%s")', $start?->format('Y-m-d'), $end?->format('Y-m-d')));

        // collect some basic vars:
        $range            = app('navigation')->getViewRange(true);
        $isCustomRange    = session('is_custom_range', false);
        if (false === $isCustomRange) {
            $start ??= session('start', today(config('app.timezone'))->startOfMonth());
            $end   ??= app('navigation')->endOfPeriod($start, $range);
        }

        // overrule start and end if necessary:
        if (true === $isCustomRange) {
            $start ??= session('start', today(config('app.timezone'))->startOfMonth());
            $end   ??= session('end', today(config('app.timezone'))->endOfMonth());
        }

        $defaultCurrency  = app('amount')->getDefaultCurrency();
        $currencies       = $this->currencyRepository->get();
        $budgeted         = '0';
        $spent            = '0';

        // new period stuff:
        $periodTitle      = app('navigation')->periodShow($start, $range);
        $prevLoop         = $this->getPreviousPeriods($start, $range);
        $nextLoop         = $this->getNextPeriods($start, $range);

        // get all available budgets:
        $availableBudgets = $this->getAllAvailableBudgets($start, $end);
        // get all active budgets:
        $budgets          = $this->getAllBudgets($start, $end, $currencies, $defaultCurrency);
        $sums             = $this->getSums($budgets);

        // get budgeted for default currency:
        if (0 === count($availableBudgets)) {
            $budgeted = $this->blRepository->budgeted($start, $end, $defaultCurrency);
            $spentArr = $this->opsRepository->sumExpenses($start, $end, null, null, $defaultCurrency);
            $spent    = $spentArr[$defaultCurrency->id]['sum'] ?? '0';
            unset($spentArr);
        }

        // number of days for consistent budgeting.
        $activeDaysPassed = $this->activeDaysPassed($start, $end); // see method description.
        $activeDaysLeft   = $this->activeDaysLeft($start, $end);   // see method description.

        // get all inactive budgets, and simply list them:
        $inactive         = $this->repository->getInactiveBudgets();

        return view(
            'budgets.index',
            compact(
                'availableBudgets',
                'budgeted',
                'spent',
                'prevLoop',
                'nextLoop',
                'budgets',
                'currencies',
                'periodTitle',
                'defaultCurrency',
                'activeDaysPassed',
                'activeDaysLeft',
                'inactive',
                'budgets',
                'start',
                'end',
                'sums'
            )
        );
    }

    private function getAllAvailableBudgets(Carbon $start, Carbon $end): array
    {
        // get all available budgets.
        $ab               = $this->abRepository->get($start, $end);
        $availableBudgets = [];

        // for each, complement with spent amount:
        /** @var AvailableBudget $entry */
        foreach ($ab as $entry) {
            $array               = $entry->toArray();
            $array['start_date'] = $entry->start_date;
            $array['end_date']   = $entry->end_date;

            // spent in period:
            $spentArr            = $this->opsRepository->sumExpenses($entry->start_date, $entry->end_date, null, null, $entry->transactionCurrency);
            $array['spent']      = $spentArr[$entry->transaction_currency_id]['sum'] ?? '0';

            // budgeted in period:
            $budgeted            = $this->blRepository->budgeted($entry->start_date, $entry->end_date, $entry->transactionCurrency);
            $array['budgeted']   = $budgeted;
            $availableBudgets[]  = $array;
            unset($spentArr);
        }

        return $availableBudgets;
    }

    private function getAllBudgets(Carbon $start, Carbon $end, Collection $currencies, TransactionCurrency $defaultCurrency): array
    {
        // get all budgets, and paginate them into $budgets.
        $collection = $this->repository->getActiveBudgets();
        $budgets    = [];
        app('log')->debug(sprintf('7) Start is "%s", end is "%s"', $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')));

        // complement budget with budget limits in range, and expenses in currency X in range.
        /** @var Budget $current */
        foreach ($collection as $current) {
            app('log')->debug(sprintf('Working on budget #%d ("%s")', $current->id, $current->name));
            $array                = $current->toArray();
            $array['spent']       = [];
            $array['spent_total'] = [];
            $array['budgeted']    = [];
            $array['attachments'] = $this->repository->getAttachments($current);
            $array['auto_budget'] = $this->repository->getAutoBudget($current);
            $budgetLimits         = $this->blRepository->getBudgetLimits($current, $start, $end);

            /** @var BudgetLimit $limit */
            foreach ($budgetLimits as $limit) {
                app('log')->debug(sprintf('Working on budget limit #%d', $limit->id));
                $currency            = $limit->transactionCurrency ?? $defaultCurrency;
                $amount              = app('steam')->bcround($limit->amount, $currency->decimal_places);
                $array['budgeted'][] = [
                    'id'                      => $limit->id,
                    'amount'                  => $amount,
                    'notes'                   => $this->blRepository->getNoteText($limit),
                    'start_date'              => $limit->start_date->isoFormat($this->monthAndDayFormat),
                    'end_date'                => $limit->end_date->isoFormat($this->monthAndDayFormat),
                    'in_range'                => $limit->start_date->isSameDay($start) && $limit->end_date->isSameDay($end),
                    'currency_id'             => $currency->id,
                    'currency_symbol'         => $currency->symbol,
                    'currency_name'           => $currency->name,
                    'currency_decimal_places' => $currency->decimal_places,
                ];
                app('log')->debug(sprintf('The amount budgeted for budget limit #%d is %s %s', $limit->id, $currency->code, $amount));
            }

            /** @var TransactionCurrency $currency */
            foreach ($currencies as $currency) {
                $spentArr = $this->opsRepository->sumExpenses($start, $end, null, new Collection([$current]), $currency);
                if (array_key_exists($currency->id, $spentArr) && array_key_exists('sum', $spentArr[$currency->id])) {
                    $array['spent'][$currency->id]['spent']                   = $spentArr[$currency->id]['sum'];
                    $array['spent'][$currency->id]['currency_id']             = $currency->id;
                    $array['spent'][$currency->id]['currency_symbol']         = $currency->symbol;
                    $array['spent'][$currency->id]['currency_decimal_places'] = $currency->decimal_places;
                }
            }
            $budgets[]            = $array;
        }

        return $budgets;
    }

    private function getSums(array $budgets): array
    {
        $sums = [
            'budgeted' => [],
            'spent'    => [],
            'left'     => [],
        ];

        /** @var array $budget */
        foreach ($budgets as $budget) {
            /** @var array $spent */
            foreach ($budget['spent'] as $spent) {
                $currencyId                           = $spent['currency_id'];
                $sums['spent'][$currencyId]
                                                      ??= [
                                                          'amount'                  => '0',
                                                          'currency_id'             => $spent['currency_id'],
                                                          'currency_symbol'         => $spent['currency_symbol'],
                                                          'currency_decimal_places' => $spent['currency_decimal_places'],
                                                      ];
                $sums['spent'][$currencyId]['amount'] = bcadd($sums['spent'][$currencyId]['amount'], $spent['spent']);
            }

            /** @var array $budgeted */
            foreach ($budget['budgeted'] as $budgeted) {
                $currencyId                              = $budgeted['currency_id'];
                $sums['budgeted'][$currencyId]
                                                         ??= [
                                                             'amount'                  => '0',
                                                             'currency_id'             => $budgeted['currency_id'],
                                                             'currency_symbol'         => $budgeted['currency_symbol'],
                                                             'currency_decimal_places' => $budgeted['currency_decimal_places'],
                                                         ];
                $sums['budgeted'][$currencyId]['amount'] = bcadd($sums['budgeted'][$currencyId]['amount'], $budgeted['amount']);

                // also calculate how much left from budgeted:
                $sums['left'][$currencyId]
                                                         ??= [
                                                             'amount'                  => '0',
                                                             'currency_id'             => $budgeted['currency_id'],
                                                             'currency_symbol'         => $budgeted['currency_symbol'],
                                                             'currency_decimal_places' => $budgeted['currency_decimal_places'],
                                                         ];
            }
        }

        // final calculation for 'left':
        /**
         * @var int $currencyId
         */
        foreach (array_keys($sums['budgeted']) as $currencyId) {
            $spent                               = $sums['spent'][$currencyId]['amount'] ?? '0';
            $budgeted                            = $sums['budgeted'][$currencyId]['amount'] ?? '0';
            $sums['left'][$currencyId]['amount'] = bcadd($spent, $budgeted);
        }

        return $sums;
    }

    public function reorder(Request $request, BudgetRepositoryInterface $repository): JsonResponse
    {
        $this->abRepository->cleanup();
        $budgetIds = $request->get('budgetIds');

        foreach ($budgetIds as $index => $budgetId) {
            $budgetId = (int) $budgetId;
            $budget   = $repository->find($budgetId);
            if (null !== $budget) {
                app('log')->debug(sprintf('Set budget #%d ("%s") to position %d', $budget->id, $budget->name, $index + 1));
                $repository->setBudgetOrder($budget, $index + 1);
            }
        }
        app('preferences')->mark();

        return response()->json(['OK']);
    }
}
