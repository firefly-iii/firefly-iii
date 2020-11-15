<?php
/*
 * IndexController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\BudgetLimit;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\DateRequest;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Transformers\BudgetLimitTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;

/**
 * Class IndexController
 */
class IndexController extends Controller
{

    private BudgetLimitRepositoryInterface $blRepository;
    private BudgetRepositoryInterface      $repository;

    /**
     * IndexController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user               = auth()->user();
                $this->repository   = app(BudgetRepositoryInterface::class);
                $this->blRepository = app(BudgetLimitRepositoryInterface::class);
                $this->repository->setUser($user);
                $this->blRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * Return all budget limits in a range.
     *
     * @return JsonResponse
     */
    public function index(DateRequest $request): JsonResponse
    {
        $dates   = $request->getAll();
        $manager = $this->getManager();
        $manager->parseIncludes('budget');
        $budgetLimits = $this->blRepository->getAllBudgetLimits($dates['start'], $dates['end']);
        $budgetLimits = $budgetLimits->slice(0, 5);
        /** @var BudgetLimitTransformer $transformer */
        $transformer = app(BudgetLimitTransformer::class);
        $transformer->setParameters($this->parameters);

        $paginator = new LengthAwarePaginator($budgetLimits, $budgetLimits->count(), 1000, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.budget_limits.index') . $this->buildParams());

        $resource = new FractalCollection($budgetLimits, $transformer, 'budget_limits');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));


        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}