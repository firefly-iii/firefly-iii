<?php
/*
 * UpdateController.php
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

namespace FireflyIII\Api\V1\Controllers\Models\Budget;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\BudgetUpdateRequest;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Transformers\BudgetTransformer;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;

/**
 * Class UpdateController
 */
class UpdateController extends Controller
{
    private BudgetRepositoryInterface $repository;

    /**
     * UpdateController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(BudgetRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }
    /**
     * Update a budget.
     *
     * @param BudgetUpdateRequest $request
     * @param Budget              $budget
     *
     * @return JsonResponse
     */
    public function update(BudgetUpdateRequest $request, Budget $budget): JsonResponse
    {
        $data    = $request->getAll();
        $budget  = $this->repository->update($budget, $data);
        $manager = $this->getManager();

        /** @var BudgetTransformer $transformer */
        $transformer = app(BudgetTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($budget, $transformer, 'budgets');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);

    }
}