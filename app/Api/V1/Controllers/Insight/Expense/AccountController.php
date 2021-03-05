<?php

/*
 * DateController.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Insight\Expense;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Insight\ExpenseRequest;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\OperationsRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Http\Api\ApiSupport;
use Illuminate\Http\JsonResponse;

/**
 * TODO per object group?
 * TODO transfers voor piggies?
 * TODO currency?
 * TODO net worth?
 *
 * Class AccountController
 *
 * Shows expense information grouped or limited by date.
 * Ie. all expenses grouped by account + currency.
 *
 * /api/v1/insight/expenses/budget
 *  Expenses per budget or no budget. Can be limited by date and by asset account.
 * /api/v1/insight/expenses/budget
 *  Also per budget limit.
 * /api/v1/insight/expenses/category
 *  Expenses per category or no category. Can be limited by date and by asset account.
 * /api/v1/insight/expenses/bill
 *  Expenses per bill or no bill. Can be limited by date and by asset account.
 *
 */
class AccountController extends Controller
{
    use ApiSupport;

    private CurrencyRepositoryInterface   $currencyRepository;
    private AccountRepositoryInterface    $repository;
    private OperationsRepositoryInterface $opsRepository;

    /**
     * AccountController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $user             = auth()->user();
                $this->repository = app(AccountRepositoryInterface::class);
                $this->repository->setUser($user);

                $this->currencyRepository = app(CurrencyRepositoryInterface::class);
                $this->currencyRepository->setUser($user);

                $this->opsRepository = app(OperationsRepositoryInterface::class);
                $this->opsRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * @param ExpenseRequest $request
     *
     * @return JsonResponse
     */
    public function expense(ExpenseRequest $request): JsonResponse
    {
        $start           = $request->getStart();
        $end             = $request->getEnd();
        $assetAccounts   = $request->getAssetAccounts();
        $expenseAccounts = $request->getExpenseAccounts();
        $expenses        = $this->opsRepository->sumExpenses($start, $end, $assetAccounts, $expenseAccounts);

        /** @var array $expense */
        foreach ($expenses as $expense) {
            $result[] = [
                'difference'       => $expense['sum'],
                'difference_float' => (float)$expense['sum'],
                'currency_id'      => (string)$expense['currency_id'],
                'currency_code'    => $expense['currency_code'],
            ];
        }

        return response()->json($result);
    }

    /**
     * @param ExpenseRequest $request
     *
     * @return JsonResponse
     */
    public function asset(ExpenseRequest $request): JsonResponse
    {
        $start         = $request->getStart();
        $end           = $request->getEnd();
        $assetAccounts = $request->getAssetAccounts();
        $expenses      = $this->opsRepository->sumExpenses($start, $end, $assetAccounts);

        /** @var array $expense */
        foreach ($expenses as $expense) {
            $result[] = [
                'difference'       => $expense['sum'],
                'difference_float' => (float)$expense['sum'],
                'currency_id'      => (string)$expense['currency_id'],
                'currency_code'    => $expense['currency_code'],
            ];
        }

        return response()->json($result);
    }

}
