<?php
/**
 * BudgetController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Api\V1\Controllers;

use Exception;
use FireflyIII\Api\V1\Requests\BudgetLimitRequest;
use FireflyIII\Api\V1\Requests\BudgetRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\BudgetLimitTransformer;
use FireflyIII\Transformers\BudgetTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;

/**
 * Class BudgetController.
 *
 */
class BudgetController extends Controller
{
    use TransactionFilter;
    /** @var BudgetLimitRepositoryInterface */
    private $blRepository;
    /** @var BudgetRepositoryInterface The budget repository */
    private $repository;

    /**
     * BudgetController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $admin */
                $admin = auth()->user();

                $this->repository   = app(BudgetRepositoryInterface::class);
                $this->blRepository = app(BudgetLimitRepositoryInterface::class);
                $this->repository->setUser($admin);
                $this->blRepository->setUser($admin);

                return $next($request);
            }
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @param Budget $budget
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function budgetLimits(Budget $budget): JsonResponse
    {
        $manager  = $this->getManager();
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $this->parameters->set('budget_id', $budget->id);
        $collection   = $this->blRepository->getBudgetLimits($budget, $this->parameters->get('start'), $this->parameters->get('end'));
        $count        = $collection->count();
        $budgetLimits = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);
        $paginator    = new LengthAwarePaginator($budgetLimits, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.budgets.budget_limits', [$budget->id]) . $this->buildParams());

        /** @var BudgetLimitTransformer $transformer */
        $transformer = app(BudgetLimitTransformer::class);
        $transformer->setParameters($this->parameters);


        $resource = new FractalCollection($budgetLimits, $transformer, 'budget_limits');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Budget $budget
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function delete(Budget $budget): JsonResponse
    {
        $this->repository->destroy($budget);

        return response()->json([], 204);
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function index(): JsonResponse
    {
        $manager = $this->getManager();

        // types to get, page size:
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

        // get list of budgets. Count it and split it.
        $collection = $this->repository->getBudgets();
        $count      = $collection->count();
        $budgets    = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($budgets, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.budgets.index') . $this->buildParams());

        /** @var BudgetTransformer $transformer */
        $transformer = app(BudgetTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($budgets, $transformer, 'budgets');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show a budget.
     *
     * @param Budget $budget
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function show(Budget $budget): JsonResponse
    {
        $manager = $this->getManager();

        /** @var BudgetTransformer $transformer */
        $transformer = app(BudgetTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($budget, $transformer, 'budgets');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store a budget.
     *
     * @param BudgetRequest $request
     *
     * @return JsonResponse
     * @throws FireflyException
     *
     */
    public function store(BudgetRequest $request): JsonResponse
    {
        $budget = $this->repository->store($request->getAll());
        if (null !== $budget) {
            $manager = $this->getManager();

            /** @var BudgetTransformer $transformer */
            $transformer = app(BudgetTransformer::class);
            $transformer->setParameters($this->parameters);

            $resource = new Item($budget, $transformer, 'budgets');

            return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
        }
        throw new FireflyException('Could not store new budget.'); // @codeCoverageIgnore
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param BudgetLimitRequest $request
     * @param Budget             $budget
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function storeBudgetLimit(BudgetLimitRequest $request, Budget $budget): JsonResponse
    {
        $data           = $request->getAll();
        $data['budget'] = $budget;
        $budgetLimit    = $this->blRepository->storeBudgetLimit($data);
        $manager        = $this->getManager();

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
     *
     * @param Budget  $budget
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function transactions(Request $request, Budget $budget): JsonResponse
    {
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

        // user can overrule page size with limit parameter.
        $limit = $this->parameters->get('limit');
        if (null !== $limit && $limit > 0) {
            $pageSize = $limit;
        }

        $type = $request->get('type') ?? 'default';
        $this->parameters->set('type', $type);

        $types   = $this->mapTransactionTypes($this->parameters->get('type'));
        $manager = $this->getManager();

        /** @var User $admin */
        $admin = auth()->user();

        // use new group collector:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setUser($admin)
            // filter on budget.
            ->setBudget($budget)
            // all info needed for the API:
            ->withAPIInformation()
            // set page size:
            ->setLimit($pageSize)
            // set page to retrieve
            ->setPage($this->parameters->get('page'))
            // set types of transactions to return.
            ->setTypes($types);

        if (null !== $this->parameters->get('start') && null !== $this->parameters->get('end')) {
            $collector->setRange($this->parameters->get('start'), $this->parameters->get('end'));
        }

        $paginator = $collector->getPaginatedGroups();
        $paginator->setPath(route('api.v1.budgets.transactions', [$budget->id]) . $this->buildParams());
        $transactions = $paginator->getCollection();

        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);


        $resource = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update a budget.
     *
     * @param BudgetRequest $request
     * @param Budget        $budget
     *
     * @return JsonResponse
     */
    public function update(BudgetRequest $request, Budget $budget): JsonResponse
    {
        $data    = $request->getAll();
        $budget  = $this->repository->update($budget, $data);
        $manager = $this->getManager();

        /** @var BudgetTransformer $transformer */
        $transformer = app(BudgetTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($budget, $transformer, 'budgets');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

}
