<?php

/*
 * ShowController.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Controllers\Model\Budget;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Generic\DateRequest;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Transformers\BudgetTransformer;
use Illuminate\Http\JsonResponse;

/**
 * Class ShowController
 * TODO lots of deprecated code here.
 */
class ShowController extends Controller
{
    private BudgetRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(BudgetRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * 2023-10-29 removed the cerSum reference, not sure where this is used atm
     * so removed from api.php. Also applies to "spent" method.
     *
     * This endpoint is documented at:
     * TODO add URL
     */
    public function budgeted(DateRequest $request, Budget $budget): JsonResponse
    {
        $data   = $request->getAll();
        $result = $this->repository->budgetedInPeriodForBudget($budget, $data['start'], $data['end']);

        return response()->json($result);
    }

    /**
     * Show a budget.
     */
    public function show(Budget $budget): JsonResponse
    {
        $transformer = new BudgetTransformer();
        $transformer->setParameters($this->parameters);

        return response()
            ->api($this->jsonApiObject('budgets', $budget, $transformer))
            ->header('Content-Type', self::CONTENT_TYPE)
        ;
    }

    /**
     * This endpoint is documented at:
     * TODO add URL
     */
    public function spent(DateRequest $request, Budget $budget): JsonResponse
    {
        $data   = $request->getAll();
        $result = $this->repository->spentInPeriodForBudget($budget, $data['start'], $data['end']);

        return response()->json($result);
    }
}
