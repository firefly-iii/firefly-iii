<?php

/*
 * BudgetController.php
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
use FireflyIII\Api\V1\Requests\Insight\GenericRequest;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\NoBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class BudgetController
 */
class BudgetController extends Controller
{
    private NoBudgetRepositoryInterface   $noRepository;
    private OperationsRepositoryInterface $opsRepository;
    private BudgetRepositoryInterface     $repository;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->opsRepository = app(OperationsRepositoryInterface::class);
                $this->repository    = app(BudgetRepositoryInterface::class);
                $this->noRepository  = app(NoBudgetRepositoryInterface::class);
                $user                = auth()->user();
                $this->opsRepository->setUser($user);
                $this->repository->setUser($user);
                $this->noRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/insight/insightExpenseBudget
     */
    public function budget(GenericRequest $request): JsonResponse
    {
        $start         = $request->getStart();
        $end           = $request->getEnd();
        $budgets       = $request->getBudgets();
        $assetAccounts = $request->getAssetAccounts();
        $result        = [];
        if (0 === $budgets->count()) {
            $budgets = $this->repository->getActiveBudgets();
        }

        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $expenses = $this->opsRepository->sumExpenses($start, $end, $assetAccounts, new Collection([$budget]));

            /** @var array $expense */
            foreach ($expenses as $expense) {
                $result[] = [
                    'id'               => (string) $budget->id,
                    'name'             => $budget->name,
                    'difference'       => $expense['sum'],
                    'difference_float' => (float) $expense['sum'], // intentional float
                    'currency_id'      => (string) $expense['currency_id'],
                    'currency_code'    => $expense['currency_code'],
                ];
            }
        }

        return response()->json($result);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/insight/insightExpenseNoBudget
     */
    public function noBudget(GenericRequest $request): JsonResponse
    {
        $start         = $request->getStart();
        $end           = $request->getEnd();
        $assetAccounts = $request->getAssetAccounts();
        $result        = [];
        $expenses      = $this->noRepository->sumExpenses($start, $end, $assetAccounts);

        /** @var array $expense */
        foreach ($expenses as $expense) {
            $result[] = [
                'difference'       => $expense['sum'],
                'difference_float' => (float) $expense['sum'], // intentional float
                'currency_id'      => (string) $expense['currency_id'],
                'currency_code'    => $expense['currency_code'],
            ];
        }

        return response()->json($result);
    }
}
