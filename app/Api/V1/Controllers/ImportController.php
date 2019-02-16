<?php
/**
 * ImportController.php
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

use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\ImportJobTransformer;
use FireflyIII\Transformers\TransactionTransformer;
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
 * Class ImportController
 */
class ImportController extends Controller
{
    use TransactionFilter;
    /** @var ImportJobRepositoryInterface Import job repository. */
    private $repository;

    /**
     * LinkTypeController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user             = auth()->user();
                $this->repository = app(ImportJobRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function listAll(Request $request): JsonResponse
    {
        // create some objects:
        $manager  = new Manager;
        $baseUrl  = $request->getSchemeAndHttpHost() . '/api/v1';
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

        // get list of accounts. Count it and split it.
        $collection = $this->repository->get();
        $count      = $collection->count();
        $importJobs = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($importJobs, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.import.list') . $this->buildParams());

        // present to user.
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var ImportJobTransformer $transformer */
        $transformer = app(ImportJobTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($importJobs, $transformer, 'import_jobs');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @param Request   $request
     * @param ImportJob $importJob
     *
     * @return JsonResponse
     */
    public function show(Request $request, ImportJob $importJob): JsonResponse
    {
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var ImportJobTransformer $transformer */
        $transformer = app(ImportJobTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($importJob, $transformer, 'import_jobs');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show all transactions
     *
     * @param Request   $request
     * @param ImportJob $importJob
     *
     * @return JsonResponse
     */
    public function transactions(Request $request, ImportJob $importJob): JsonResponse
    {
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $type     = $request->get('type') ?? 'default';
        $this->parameters->set('type', $type);

        $types   = $this->mapTransactionTypes($this->parameters->get('type'));
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $tag          = $importJob->tag;
        $transactions = new Collection();
        $paginator    = new LengthAwarePaginator($transactions, 0, $pageSize);
        $paginator->setPath(route('api.v1.import.transactions', [$importJob->key]) . $this->buildParams());

        if (null !== $tag) {
            /** @var User $admin */
            $admin = auth()->user();
            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setUser($admin);
            $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
            $collector->setAllAssetAccounts();
            $collector->setTag($tag);

            if (\in_array(TransactionType::TRANSFER, $types, true)) {
                $collector->removeFilter(InternalTransferFilter::class);
            }

            if (null !== $this->parameters->get('start') && null !== $this->parameters->get('end')) {
                $collector->setRange($this->parameters->get('start'), $this->parameters->get('end'));
            }
            $collector->setLimit($pageSize)->setPage($this->parameters->get('page'));
            $collector->setTypes($types);
            $paginator = $collector->getPaginatedTransactions();
            $paginator->setPath(route('api.v1.transactions.index') . $this->buildParams());
            $transactions = $paginator->getCollection();
        }

        /** @var TransactionTransformer $transformer */
        $transformer = app(TransactionTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

}
