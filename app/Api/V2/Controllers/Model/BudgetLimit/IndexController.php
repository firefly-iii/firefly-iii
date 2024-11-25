<?php

/*
 * IndexController.php
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

namespace FireflyIII\Api\V2\Controllers\Model\BudgetLimit;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Transformers\V2\BudgetLimitTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexController extends Controller
{
    private BudgetLimitRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(BudgetLimitRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * TODO add URL
     */
    public function index(Budget $budget): JsonResponse
    {
        $pageSize    = $this->parameters->get('limit');
        $collection  = $this->repository->getBudgetLimits($budget);
        $total       = $collection->count();
        $collection->slice($pageSize * $this->parameters->get('page'), $pageSize);

        $paginator   = new LengthAwarePaginator($collection, $total, $pageSize, $this->parameters->get('page'));
        $transformer = new BudgetLimitTransformer();

        return response()->api($this->jsonApiList('budget-limits', $paginator, $transformer))->header('Content-Type', self::CONTENT_TYPE);
    }
}
