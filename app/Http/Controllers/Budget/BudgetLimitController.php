<?php

/**
 * BudgetLimitController.php
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
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Class BudgetLimitController
 */
class BudgetLimitController extends Controller
{
    use DateCalculation;

    private BudgetLimitRepositoryInterface $blRepository;
    private CurrencyRepositoryInterface    $currencyRepos;
    private OperationsRepositoryInterface  $opsRepository;
    private BudgetRepositoryInterface      $repository;

    /**
     * AmountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.budgets'));
                app('view')->share('mainTitleIcon', 'fa-pie-chart');
                $this->repository    = app(BudgetRepositoryInterface::class);
                $this->opsRepository = app(OperationsRepositoryInterface::class);
                $this->blRepository  = app(BudgetLimitRepositoryInterface::class);
                $this->currencyRepos = app(CurrencyRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @return Factory|View
     */
    public function create(Budget $budget, Carbon $start, Carbon $end)
    {
        $collection   = $this->currencyRepos->get();
        $budgetLimits = $this->blRepository->getBudgetLimits($budget, $start, $end);

        // remove already budgeted currencies with the same date range
        $currencies   = $collection->filter(
            static function (TransactionCurrency $currency) use ($budgetLimits, $start, $end) {
                /** @var BudgetLimit $limit */
                foreach ($budgetLimits as $limit) {
                    if ($limit->transaction_currency_id === $currency->id && $limit->start_date->isSameDay($start) && $limit->end_date->isSameDay($end)
                    ) {
                        return false;
                    }
                }

                return true;
            }
        );

        return view('budgets.budget-limits.create', compact('start', 'end', 'currencies', 'budget'));
    }

    /**
     * @return Redirector|RedirectResponse
     */
    public function delete(BudgetLimit $budgetLimit)
    {
        $this->blRepository->destroyBudgetLimit($budgetLimit);
        session()->flash('success', trans('firefly.deleted_bl'));

        return redirect(route('budgets.index'));
    }

    /**
     * TODO why redirect AND json response?
     *
     * @throws FireflyException
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        app('log')->debug('Going to store new budget-limit.', $request->all());
        // first search for existing one and update it if necessary.
        $currency = $this->currencyRepos->find((int)$request->get('transaction_currency_id'));
        $budget   = $this->repository->find((int)$request->get('budget_id'));
        if (null === $currency || null === $budget) {
            throw new FireflyException('No valid currency or budget.');
        }
        $start    = Carbon::createFromFormat('Y-m-d', $request->get('start'));
        $end      = Carbon::createFromFormat('Y-m-d', $request->get('end'));

        if (null === $start || null === $end) {
            return response()->json([]);
        }

        $amount   = (string)$request->get('amount');
        $start->startOfDay();
        $end->startOfDay();

        if ('' === $amount) {
            return response()->json([]);
        }

        app('log')->debug(sprintf('Start: %s, end: %s', $start->format('Y-m-d'), $end->format('Y-m-d')));

        $limit    = $this->blRepository->find($budget, $currency, $start, $end);

        // sanity check on amount:
        if (0 === bccomp($amount, '0')) {
            if (null !== $limit) {
                $this->blRepository->destroyBudgetLimit($limit);
            }

            // return empty=ish array:
            return response()->json([]);
        }
        if ((int)$amount > 268435456) { // intentional cast to integer
            $amount = '268435456';
        }
        if (-1 === bccomp($amount, '0')) {
            $amount = bcmul($amount, '-1');
        }

        if (null !== $limit) {
            $limit->amount = $amount;
            $limit->save();
        }
        if (null === $limit) {
            $limit = $this->blRepository->store(
                [
                    'budget_id'   => $request->get('budget_id'),
                    'currency_id' => (int)$request->get('transaction_currency_id'),
                    'start_date'  => $start,
                    'end_date'    => $end,
                    'amount'      => $amount,
                ]
            );
        }

        if ($request->expectsJson()) {
            $array                           = $limit->toArray();
            // add some extra metadata:
            $spentArr                        = $this->opsRepository->sumExpenses($limit->start_date, $limit->end_date, null, new Collection([$budget]), $currency);
            $array['spent']                  = $spentArr[$currency->id]['sum'] ?? '0';
            $array['left_formatted']         = app('amount')->formatAnything($limit->transactionCurrency, bcadd($array['spent'], $array['amount']));
            $array['amount_formatted']       = app('amount')->formatAnything($limit->transactionCurrency, $limit['amount']);
            $array['days_left']              = (string)$this->activeDaysLeft($start, $end);
            // left per day:
            $array['left_per_day']           = 0 === bccomp('0', $array['days_left']) ? bcadd($array['spent'], $array['amount']) : bcdiv(bcadd($array['spent'], $array['amount']), $array['days_left']);

            // left per day formatted.
            $array['left_per_day_formatted'] = app('amount')->formatAnything($limit->transactionCurrency, $array['left_per_day']);

            return response()->json($array);
        }

        return redirect(route('budgets.index'));
    }

    public function update(Request $request, BudgetLimit $budgetLimit): JsonResponse
    {
        $amount                          = (string)$request->get('amount');
        if ('' === $amount) {
            $amount = '0';
        }
        if ((int)$amount > 268435456) { // 268 million, intentional integer
            $amount = '268435456';
        }
        // sanity check on amount:
        if (0 === bccomp($amount, '0')) {
            $budgetId = $budgetLimit->budget_id;
            $currency = $budgetLimit->transactionCurrency;
            $this->blRepository->destroyBudgetLimit($budgetLimit);
            $array    = [
                'budget_id'               => $budgetId,
                'left_formatted'          => app('amount')->formatAnything($currency, '0'),
                'left_per_day_formatted'  => app('amount')->formatAnything($currency, '0'),
                'transaction_currency_id' => $currency->id,
            ];

            return response()->json($array);
        }

        if (-1 === bccomp($amount, '0')) {
            $amount = bcmul($amount, '-1');
        }

        $limit                           = $this->blRepository->update($budgetLimit, ['amount' => $amount]);
        app('preferences')->mark();
        $array                           = $limit->toArray();

        $spentArr                        = $this->opsRepository->sumExpenses(
            $limit->start_date,
            $limit->end_date,
            null,
            new Collection([$budgetLimit->budget]),
            $budgetLimit->transactionCurrency
        );
        $daysLeft                        = $this->activeDaysLeft($limit->start_date, $limit->end_date);
        $array['spent']                  = $spentArr[$budgetLimit->transactionCurrency->id]['sum'] ?? '0';
        $array['left_formatted']         = app('amount')->formatAnything($limit->transactionCurrency, bcadd($array['spent'], $array['amount']));
        $array['amount_formatted']       = app('amount')->formatAnything($limit->transactionCurrency, $limit['amount']);
        $array['days_left']              = (string)$daysLeft;
        $array['left_per_day']           = 0 === $daysLeft ? bcadd($array['spent'], $array['amount']) : bcdiv(bcadd($array['spent'], $array['amount']), $array['days_left']);

        // left per day formatted.
        $array['amount']                 = app('steam')->bcround($limit['amount'], $limit->transactionCurrency->decimal_places);
        $array['left_per_day_formatted'] = app('amount')->formatAnything($limit->transactionCurrency, $array['left_per_day']);

        return response()->json($array);
    }
}
