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
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Log;

/**
 *
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
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Factory|View
     */
    public function create(Budget $budget, Carbon $start, Carbon $end)
    {
        $collection   = $this->currencyRepos->get();
        $budgetLimits = $this->blRepository->getBudgetLimits($budget, $start, $end);

        // remove already budgeted currencies:
        $currencies = $collection->filter(
            static function (TransactionCurrency $currency) use ($budgetLimits) {
                /** @var AvailableBudget $budget */
                foreach ($budgetLimits as $budget) {
                    if ($budget->transaction_currency_id === $currency->id) {
                        return false;
                    }
                }

                return true;
            }
        );

        return prefixView('budgets.budget-limits.create', compact('start', 'end', 'currencies', 'budget'));
    }

    /**
     * @param Request     $request
     * @param BudgetLimit $budgetLimit
     *
     * @return RedirectResponse|Redirector
     */
    public function delete(Request $request, BudgetLimit $budgetLimit)
    {
        $this->blRepository->destroyBudgetLimit($budgetLimit);
        session()->flash('success', trans('firefly.deleted_bl'));

        return redirect(route('budgets.index'));
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse|RedirectResponse|Redirector
     * @throws FireflyException
     */
    public function store(Request $request)
    {
        Log::debug('Going to store new budget-limit.', $request->all());
        // first search for existing one and update it if necessary.
        $currency = $this->currencyRepos->find((int)$request->get('transaction_currency_id'));
        $budget   = $this->repository->findNull((int)$request->get('budget_id'));
        if (null === $currency || null === $budget) {
            throw new FireflyException('No valid currency or budget.');
        }
        $start  = Carbon::createFromFormat('Y-m-d', $request->get('start'));
        $end    = Carbon::createFromFormat('Y-m-d', $request->get('end'));
        $amount = (string)$request->get('amount');
        $start->startOfDay();
        $end->startOfDay();

        if ('' === $amount) {
            return response()->json([]);
        }

        Log::debug(sprintf('Start: %s, end: %s', $start->format('Y-m-d'), $end->format('Y-m-d')));

        $limit = $this->blRepository->find($budget, $currency, $start, $end);
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
            $array = $limit->toArray();
            // add some extra meta data:
            $spentArr                  = $this->opsRepository->sumExpenses($limit->start_date, $limit->end_date, null, new Collection([$budget]), $currency);
            $array['spent']            = $spentArr[$currency->id]['sum'] ?? '0';
            $array['left_formatted']   = app('amount')->formatAnything($limit->transactionCurrency, bcadd($array['spent'], $array['amount']));
            $array['amount_formatted'] = app('amount')->formatAnything($limit->transactionCurrency, $limit['amount']);
            $array['days_left']        = (string)$this->activeDaysLeft($start, $end);
            // left per day:
            $array['left_per_day'] = bcdiv(bcadd($array['spent'], $array['amount']), $array['days_left']);

            // left per day formatted.
            $array['left_per_day_formatted'] = app('amount')->formatAnything($limit->transactionCurrency, $array['left_per_day']);

            return response()->json($array);
        }

        return response()->json([]);
    }

    /**
     * @param Request     $request
     * @param BudgetLimit $budgetLimit
     *
     * @return JsonResponse
     */
    public function update(Request $request, BudgetLimit $budgetLimit): JsonResponse
    {
        $amount = (string)$request->get('amount');
        if ('' === $amount) {
            $amount = '0';
        }

        $limit = $this->blRepository->update($budgetLimit, ['amount' => $amount]);
        $array = $limit->toArray();

        $spentArr                  = $this->opsRepository->sumExpenses(
            $limit->start_date,
            $limit->end_date,
            null,
            new Collection([$budgetLimit->budget]),
            $budgetLimit->transactionCurrency
        );
        $array['spent']            = $spentArr[$budgetLimit->transactionCurrency->id]['sum'] ?? '0';
        $array['left_formatted']   = app('amount')->formatAnything($limit->transactionCurrency, bcadd($array['spent'], $array['amount']));
        $array['amount_formatted'] = app('amount')->formatAnything($limit->transactionCurrency, $limit['amount']);
        $array['days_left']        = (string)$this->activeDaysLeft($limit->start_date, $limit->end_date);
        // left per day:
        $array['left_per_day'] = bcdiv(bcadd($array['spent'], $array['amount']), $array['days_left']);

        // left per day formatted.
        $array['amount']                 = number_format((float)$limit['amount'], $limit->transactionCurrency->decimal_places, '.', '');
        $array['left_per_day_formatted'] = app('amount')->formatAnything($limit->transactionCurrency, $array['left_per_day']);

        return response()->json($array);
    }
}
