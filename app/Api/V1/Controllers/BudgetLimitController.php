<?php
/**
 * BudgetLimitController.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Api\V1\Controllers;


use FireflyIII\Api\V1\Requests\BudgetLimitRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Transformers\BudgetLimitTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;


/**
 * Class BudgetLimitController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BudgetLimitController extends Controller
{
    /** @var BudgetRepositoryInterface The budget repository */
    private $repository;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user             = auth()->user();
                $this->repository = app(BudgetRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param BudgetLimit $budgetLimit
     *
     * @return JsonResponse
     */
    public function delete(BudgetLimit $budgetLimit): JsonResponse
    {
        $this->repository->destroyBudgetLimit($budgetLimit);

        return response()->json([], 204);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $manager  = new Manager;
        $baseUrl  = $request->getSchemeAndHttpHost() . '/api/v1';
        $budgetId = (int)($request->get('budget_id') ?? 0);
        $budget   = $this->repository->findNull($budgetId);
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $this->parameters->set('budget_id', $budgetId);

        $collection = new Collection;
        if (null === $budget) {
            $collection = $this->repository->getAllBudgetLimits($this->parameters->get('start'), $this->parameters->get('end'));
        }
        if (null !== $budget) {
            $collection = $this->repository->getBudgetLimits($budget, $this->parameters->get('start'), $this->parameters->get('end'));
        }

        $count        = $collection->count();
        $budgetLimits = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);
        $paginator    = new LengthAwarePaginator($budgetLimits, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.budget_limits.index') . $this->buildParams());

        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        $resource = new FractalCollection($budgetLimits, new BudgetLimitTransformer($this->parameters), 'budget_limits');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Display the specified resource.
     *
     * @param Request     $request
     * @param BudgetLimit $budgetLimit
     *
     * @return JsonResponse
     */
    public function show(Request $request, BudgetLimit $budgetLimit): JsonResponse
    {
        $manager = new Manager;

        // add include parameter:
        $include = $request->get('include') ?? '';
        $manager->parseIncludes($include);

        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        $resource = new Item($budgetLimit, new BudgetLimitTransformer($this->parameters), 'budget_limits');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param BudgetLimitRequest $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function store(BudgetLimitRequest $request): JsonResponse
    {
        $data   = $request->getAll();
        $budget = $this->repository->findNull($data['budget_id']);
        if (null === $budget) {
            throw new FireflyException('Unknown budget.');
        }
        $data['budget'] = $budget;
        $budgetLimit    = $this->repository->storeBudgetLimit($data);
        $manager        = new Manager;
        $baseUrl        = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $resource = new Item($budgetLimit, new BudgetLimitTransformer($this->parameters), 'budget_limits');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param BudgetLimitRequest $request
     * @param BudgetLimit        $budgetLimit
     *
     * @return JsonResponse
     */
    public function update(BudgetLimitRequest $request, BudgetLimit $budgetLimit): JsonResponse
    {
        $data   = $request->getAll();
        $budget = $this->repository->findNull($data['budget_id']);
        if (null === $budget) {
            $budget = $budgetLimit->budget;
        }
        $data['budget'] = $budget;
        $budgetLimit    = $this->repository->updateBudgetLimit($budgetLimit, $data);
        $manager        = new Manager;
        $baseUrl        = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $resource = new Item($budgetLimit, new BudgetLimitTransformer($this->parameters), 'budget_limits');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }
}
