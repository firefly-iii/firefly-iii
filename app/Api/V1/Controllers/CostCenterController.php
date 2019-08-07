<?php
/**
 * CostCenterController.php
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

use FireflyIII\Api\V1\Requests\CostCenterRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Models\CostCenter;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\CostCenter\CostCenterRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\CostCenterTransformer;
use FireflyIII\Transformers\TransactionTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;

/**
 * Class CostCenterController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CostCenterController extends Controller
{
    use TransactionFilter;
    /** @var CostCenterRepositoryInterface The cost center repository */
    private $repository;

    /**
     * CostCenterController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $admin */
                $admin = auth()->user();

                /** @var CostCenterRepositoryInterface repository */
                $this->repository = app(CostCenterRepositoryInterface::class);
                $this->repository->setUser($admin);

                return $next($request);
            }
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param CostCenter $costCenter
     *
     * @return JsonResponse
     */
    public function delete(CostCenter $costCenter): JsonResponse
    {
        $this->repository->destroy($costCenter);

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
        // create some objects:
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';

        // types to get, page size:
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

        // get list of budgets. Count it and split it.
        $collection = $this->repository->getCostCenters();
        $count      = $collection->count();
        $costCenters = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($costCenters, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.cost_centers.index') . $this->buildParams());

        // present to user.
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var CostCenterTransformer $transformer */
        $transformer = app(CostCenterTransformer::class);
        $transformer->setParameters($this->parameters);


        $resource = new FractalCollection($costCenters, $transformer, 'cost_centers');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }


    /**
     * Show the cost center.
     *
     * @param Request  $request
     * @param CostCenter $costCenter
     *
     * @return JsonResponse
     */
    public function show(Request $request, CostCenter $costCenter): JsonResponse
    {
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var CostCenterTransformer $transformer */
        $transformer = app(CostCenterTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($costCenter, $transformer, 'cost_centers');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store new cost center.
     *
     * @param CostCenterRequest $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function store(CostCenterRequest $request): JsonResponse
    {
        $costCenter = $this->repository->store($request->getAll());
        if (null !== $costCenter) {
            $manager = new Manager();
            $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
            $manager->setSerializer(new JsonApiSerializer($baseUrl));

            /** @var CostCenterTransformer $transformer */
            $transformer = app(CostCenterTransformer::class);
            $transformer->setParameters($this->parameters);

            $resource = new Item($costCenter, $transformer, 'cost_centers');

            return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
        }
        throw new FireflyException('Could not store new cost center.'); // @codeCoverageIgnore
    }

    /**
     * Show all transactions.
     *
     * @param Request  $request
     *
     * @param CostCenter $costCenter
     *
     * @return JsonResponse
     */
    public function transactions(Request $request, CostCenter $costCenter): JsonResponse
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
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($admin);
        $collector->withOpposingAccount()->withCostCenterInformation()->withBudgetInformation();
        $collector->setAllAssetAccounts();
        $collector->setCostCenter($costCenter);

        if (\in_array(TransactionType::TRANSFER, $types, true)) {
            $collector->removeFilter(InternalTransferFilter::class);
        }

        if (null !== $this->parameters->get('start') && null !== $this->parameters->get('end')) {
            $collector->setRange($this->parameters->get('start'), $this->parameters->get('end'));
        }
        $collector->setLimit($pageSize)->setPage($this->parameters->get('page'));
        $collector->setTypes($types);
        $paginator = $collector->getPaginatedTransactions();
        $paginator->setPath(route('api.v1.cost_centers.transactions', [$costCenter->id]) . $this->buildParams());
        $transactions = $paginator->getCollection();

        /** @var TransactionTransformer $transformer */
        $transformer = app(TransactionTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update the cost center.
     *
     * @param CostCenterRequest $request
     * @param CostCenter        $costCenter
     *
     * @return JsonResponse
     */
    public function update(CostCenterRequest $request, CostCenter $costCenter): JsonResponse
    {
        $data     = $request->getAll();
        $costCenter = $this->repository->update($costCenter, $data);
        $manager  = new Manager();
        $baseUrl  = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var CostCenterTransformer $transformer */
        $transformer = app(CostCenterTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($costCenter, $transformer, 'cost_centers');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

}
