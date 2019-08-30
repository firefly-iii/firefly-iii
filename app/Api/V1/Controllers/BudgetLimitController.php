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
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\BudgetLimitTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
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
 */
class BudgetLimitController extends Controller
{
    use TransactionFilter;
    /** @var BudgetRepositoryInterface The budget repository */
    private $repository;

    /** @var BudgetLimitRepositoryInterface */
    private $blRepository;


    /**
     * BudgetLimitController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user             = auth()->user();
                $this->repository = app(BudgetRepositoryInterface::class);
                $this->blRepository = app(BudgetLimitRepositoryInterface::class);
                $this->repository->setUser($user);
                $this->blRepository->setUser($user);

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
     * @codeCoverageIgnore
     */
    public function delete(BudgetLimit $budgetLimit): JsonResponse
    {
        $this->blRepository->destroyBudgetLimit($budgetLimit);

        return response()->json([], 204);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @codeCoverageIgnore
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
            $collection = $this->blRepository->getAllBudgetLimits($this->parameters->get('start'), $this->parameters->get('end'));
        }
        if (null !== $budget) {
            $collection = $this->repository->getBudgetLimits($budget, $this->parameters->get('start'), $this->parameters->get('end'));
        }

        $count        = $collection->count();
        $budgetLimits = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);
        $paginator    = new LengthAwarePaginator($budgetLimits, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.budget_limits.index') . $this->buildParams());

        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var BudgetLimitTransformer $transformer */
        $transformer = app(BudgetLimitTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($budgetLimits, $transformer, 'budget_limits');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param BudgetLimit $budgetLimit
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function show(Request $request, BudgetLimit $budgetLimit): JsonResponse
    {
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var BudgetLimitTransformer $transformer */
        $transformer = app(BudgetLimitTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($budgetLimit, $transformer, 'budget_limits');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param BudgetLimitRequest $request
     *
     * @return JsonResponse
     * @throws FireflyException
     *
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

        /** @var BudgetLimitTransformer $transformer */
        $transformer = app(BudgetLimitTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($budgetLimit, $transformer, 'budget_limits');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show all transactions.
     *
     * @param Request $request
     * @param BudgetLimit $budgetLimit
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function transactions(Request $request, BudgetLimit $budgetLimit): JsonResponse
    {
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $type     = $request->get('type') ?? 'default';
        $this->parameters->set('type', $type);

        $types   = $this->mapTransactionTypes($this->parameters->get('type'));
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var User $admin */
        $admin = auth()->user();

        // use new group collector:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setUser($admin)
            // filter on budget.
            ->setBudget($budgetLimit->budget)
            // all info needed for the API:
            ->withAPIInformation()
            // set page size:
            ->setLimit($pageSize)
            // set page to retrieve
            ->setPage($this->parameters->get('page'))
            // set types of transactions to return.
            ->setTypes($types);

        $collector->setRange($budgetLimit->start_date, $budgetLimit->end_date);
        $collector->setTypes($types);
        $paginator = $collector->getPaginatedGroups();
        $paginator->setPath(route('api.v1.budget_limits.transactions', [$budgetLimit->id]) . $this->buildParams());
        $transactions = $paginator->getCollection();

        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param BudgetLimitRequest $request
     * @param BudgetLimit $budgetLimit
     *
     * @return JsonResponse
     */
    public function update(BudgetLimitRequest $request, BudgetLimit $budgetLimit): JsonResponse
    {
        $data           = $request->getAll();
        $data['budget'] = $budgetLimit->budget;
        $budgetLimit    = $this->repository->updateBudgetLimit($budgetLimit, $data);
        $manager        = new Manager;
        $baseUrl        = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var BudgetLimitTransformer $transformer */
        $transformer = app(BudgetLimitTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($budgetLimit, $transformer, 'budget_limits');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }
}
