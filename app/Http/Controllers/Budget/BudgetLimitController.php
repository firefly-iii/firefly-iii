<?php
/**
 * BudgetLimitController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\Budget;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 *
 * Class BudgetLimitController
 */
class BudgetLimitController extends Controller
{

    /** @var AvailableBudgetRepositoryInterface */
    private $abRepository;
    /** @var BudgetLimitRepositoryInterface */
    private $blRepository;
    /** @var CurrencyRepositoryInterface */
    private $currencyRepos;
    /** @var OperationsRepositoryInterface */
    private $opsRepository;
    /** @var BudgetRepositoryInterface The budget repository */
    private $repository;

    /**
     * AmountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.budgets'));
                app('view')->share('mainTitleIcon', 'fa-tasks');
                $this->repository    = app(BudgetRepositoryInterface::class);
                $this->opsRepository = app(OperationsRepositoryInterface::class);
                $this->abRepository  = app(AvailableBudgetRepositoryInterface::class);
                $this->blRepository  = app(BudgetLimitRepositoryInterface::class);
                $this->currencyRepos = app(CurrencyRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Request     $request
     * @param BudgetLimit $budgetLimit
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
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
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $limit = $this->blRepository->store(
            [
                'budget_id'               => $request->get('budget_id'),
                'transaction_currency_id' => $request->get('transaction_currency_id'),
                'start_date'              => $request->get('start'),
                'end_date'                => $request->get('end'),
                'amount'                  => $request->get('amount'),
            ]
        );

        return response()->json($limit->toArray());
    }

    /**
     * @param Request     $request
     * @param BudgetLimit $budgetLimit
     *
     * @return JsonResponse
     */
    public function update(Request $request, BudgetLimit $budgetLimit): JsonResponse
    {
        $amount = $request->get('amount');

        return response()->json($this->blRepository->update($budgetLimit, ['amount' => $amount])->toArray());

    }

}