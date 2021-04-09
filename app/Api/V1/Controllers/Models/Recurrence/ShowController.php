<?php
/*
 * ShowController.php
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

namespace FireflyIII\Api\V1\Controllers\Models\Recurrence;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Models\Recurrence;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Transformers\RecurrenceTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    private RecurringRepositoryInterface $repository;

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
                $this->repository = app(RecurringRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * List all of them.
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
        $collection = $this->repository->getAll();
        $count      = $collection->count();
        $piggyBanks = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($piggyBanks, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.recurrences.index') . $this->buildParams());

        /** @var RecurrenceTransformer $transformer */
        $transformer = app(RecurrenceTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($piggyBanks, $transformer, 'recurrences');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);

    }

    /**
     * List single resource.
     *
     * @param Recurrence $recurrence
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function show(Recurrence $recurrence): JsonResponse
    {
        $manager = $this->getManager();

        /** @var RecurrenceTransformer $transformer */
        $transformer = app(RecurrenceTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($recurrence, $transformer, 'recurrences');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
