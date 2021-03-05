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

namespace FireflyIII\Api\V1\Controllers\Insight\Expense;


use Carbon\Carbon;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\DateRequest;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class BudgetController
 */
class BudgetController extends Controller
{
    private OperationsRepositoryInterface $opsRepository;
    private BudgetRepositoryInterface     $repository;

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
                $this->opsRepository = app(OperationsRepositoryInterface::class);
                $this->repository    = app(BudgetRepositoryInterface::class);
                $this->opsRepository->setUser(auth()->user());
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * @param DateRequest $request
     *
     * @return JsonResponse
     */
    public function budget(DateRequest $request): JsonResponse
    {
        $dates = $request->getAll();
        /** @var Carbon $start */
        $start = $dates['start'];
        /** @var Carbon $end */
        $end     = $dates['end'];
        $result  = [];
        $budgets = $this->repository->getActiveBudgets();
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $expenses = $this->opsRepository->sumExpenses($start, $end, null, new Collection([$budget]), null);
            /** @var array $expense */
            foreach ($expenses as $expense) {
                $result[] = [
                    'id'               => (string)$budget->id,
                    'name'             => $budget->name,
                    'difference'       => $expense['sum'],
                    'difference_float' => (float)$expense['sum'],
                    'currency_id'      => (string)$expense['currency_id'],
                    'currency_code'    => $expense['currency_code'],
                ];
            }
        }

        return response()->json($result);
    }

}