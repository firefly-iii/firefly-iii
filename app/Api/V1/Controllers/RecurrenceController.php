<?php
/**
 * RecurrenceController.php
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

use FireflyIII\Api\V1\Requests\RecurrenceStoreRequest;
use FireflyIII\Api\V1\Requests\RecurrenceUpdateRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Recurrence;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Support\Cronjobs\RecurringCronjob;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\RecurrenceTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;
use Log;

/**
 * Class RecurrenceController
 */
class RecurrenceController extends Controller
{
    use TransactionFilter;
    /** @var RecurringRepositoryInterface The recurring transaction repository */
    private $repository;

    /**
     * RecurrenceController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user = auth()->user();

                /** @var RecurringRepositoryInterface repository */
                $this->repository = app(RecurringRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * Delete the resource.
     *
     * @param Recurrence $recurrence
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function delete(Recurrence $recurrence): JsonResponse
    {
        $this->repository->destroy($recurrence);

        return response()->json([], 204);
    }

    /**
     * List all of them.
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function index(Request $request): JsonResponse
    {
        // create some objects:
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';

        // types to get, page size:
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

        // get list of budgets. Count it and split it.
        $collection = $this->repository->getAll();
        $count      = $collection->count();
        $piggyBanks = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($piggyBanks, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.recurrences.index') . $this->buildParams());

        // present to user.
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var RecurrenceTransformer $transformer */
        $transformer = app(RecurrenceTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($piggyBanks, $transformer, 'recurrences');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * List single resource.
     *
     * @param Request $request
     * @param Recurrence $recurrence
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function show(Request $request, Recurrence $recurrence): JsonResponse
    {
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var RecurrenceTransformer $transformer */
        $transformer = app(RecurrenceTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($recurrence, $transformer, 'recurrences');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');


    }

    /**
     * Store new object.
     *
     * @param RecurrenceStoreRequest $request
     *
     * @return JsonResponse
     */
    public function store(RecurrenceStoreRequest $request): JsonResponse
    {
        $recurrence = $this->repository->store($request->getAllRecurrenceData());
        $manager    = new Manager();
        $baseUrl    = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var RecurrenceTransformer $transformer */
        $transformer = app(RecurrenceTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($recurrence, $transformer, 'recurrences');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show transactions for this recurrence.
     *
     * @param Request $request
     * @param Recurrence $recurrence
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function transactions(Request $request, Recurrence $recurrence): JsonResponse
    {
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $type     = $request->get('type') ?? 'default';
        $this->parameters->set('type', $type);

        $types   = $this->mapTransactionTypes($this->parameters->get('type'));
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        // whatever is returned by the query, it must be part of these journals:
        $journalIds = $this->repository->getJournalIds($recurrence);

        /** @var User $admin */
        $admin = auth()->user();

        // use new group collector:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setUser($admin)
            // filter on journal IDs.
            ->setJournalIds($journalIds)
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
        $paginator->setPath(route('api.v1.transactions.index') . $this->buildParams());
        $transactions = $paginator->getCollection();

        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @return JsonResponse
     * @throws FireflyException
     * @codeCoverageIgnore
     */
    public function trigger(): JsonResponse
    {
        /** @var RecurringCronjob $recurring */
        $recurring = app(RecurringCronjob::class);
        try {
            $result = $recurring->fire();
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            throw new FireflyException('Could not fire recurring cron job.');
        }
        if (false === $result) {
            return response()->json([], 204);
        }
        if (true === $result) {
            return response()->json();
        }

        return response()->json([], 418); // @codeCoverageIgnore
    }

    /**
     * Update single recurrence.
     *
     * @param RecurrenceUpdateRequest $request
     * @param Recurrence $recurrence
     *
     * @return JsonResponse
     */
    public function update(RecurrenceUpdateRequest $request, Recurrence $recurrence): JsonResponse
    {
        $data     = $request->getAllRecurrenceData();
        $category = $this->repository->update($recurrence, $data);
        $manager  = new Manager();
        $baseUrl  = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        /** @var RecurrenceTransformer $transformer */
        $transformer = app(RecurrenceTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($category, $transformer, 'recurrences');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }
}
