<?php

/*
 * ListController.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Controllers\Model\BudgetLimit;

use FireflyIII\Api\V2\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Class ListController
 */
class ListController extends Controller
{
    //    private BudgetLimitRepositoryInterface $repository;
    //
    //    public function __construct()
    //    {
    //        parent::__construct();
    //        $this->middleware(
    //            function ($request, $next) {
    //                $this->repository = app(BudgetLimitRepositoryInterface::class);
    //
    //                return $next($request);
    //            }
    //        );
    //    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v2)#/budgets/listBudgetLimitByBudget
     * // DateRequest $request, Budget $budget
     */
    public function index(): JsonResponse
    {
        return response()->json();
        //        throw new FireflyException('Needs refactoring, move to IndexController.');
        //        $pageSize   = $this->parameters->get('limit');
        //        $dates      = $request->getAll();
        //        $collection = $this->repository->getBudgetLimits($budget, $dates['start'], $dates['end']);
        //        $total      = $collection->count();
        //        $collection->slice($pageSize * $this->parameters->get('page'), $pageSize);
        //
        //        $paginator   = new LengthAwarePaginator($collection, $total, $pageSize, $this->parameters->get('page'));
        //        $transformer = new BudgetLimitTransformer();
        //
        //        return response()
        //            ->api($this->jsonApiList('budget-limits', $paginator, $transformer))
        //            ->header('Content-Type', self::CONTENT_TYPE);
    }
}
