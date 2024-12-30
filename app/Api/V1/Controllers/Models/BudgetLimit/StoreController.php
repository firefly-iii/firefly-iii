<?php

/*
 * StoreController.php
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

namespace FireflyIII\Api\V1\Controllers\Models\BudgetLimit;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\BudgetLimit\StoreRequest;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Transformers\BudgetLimitTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;

/**
 * Class StoreController
 */
class StoreController extends Controller
{
    private BudgetLimitRepositoryInterface $blRepository;

    /**
     * BudgetLimitController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user               = auth()->user();
                $this->blRepository = app(BudgetLimitRepositoryInterface::class);
                $this->blRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/budgets/storeBudgetLimit
     *
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request, Budget $budget): JsonResponse
    {
        $data               = $request->getAll();
        $data['start_date'] = $data['start'];
        $data['end_date']   = $data['end'];
        $data['notes']      = $data['notes'];
        $data['budget_id']  = $budget->id;

        $budgetLimit        = $this->blRepository->store($data);
        $manager            = $this->getManager();

        /** @var BudgetLimitTransformer $transformer */
        $transformer        = app(BudgetLimitTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource           = new Item($budgetLimit, $transformer, 'budget_limits');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
