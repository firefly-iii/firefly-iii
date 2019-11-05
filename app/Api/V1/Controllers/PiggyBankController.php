<?php
/**
 * PiggyBankController.php
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

use FireflyIII\Api\V1\Requests\PiggyBankRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Transformers\PiggyBankEventTransformer;
use FireflyIII\Transformers\PiggyBankTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;

/**
 * Class PiggyBankController.
 *
 */
class PiggyBankController extends Controller
{

    /** @var PiggyBankRepositoryInterface The piggy bank repository */
    private $repository;

    /**
     * PiggyBankController constructor.
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

                $this->repository = app(PiggyBankRepositoryInterface::class);
                $this->repository->setUser($admin);


                return $next($request);
            }
        );
    }

    /**
     * Delete the resource.
     *
     * @param PiggyBank $piggyBank
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function delete(PiggyBank $piggyBank): JsonResponse
    {
        $this->repository->destroy($piggyBank);

        return response()->json([], 204);
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
        $collection = $this->repository->getPiggyBanks();
        $count      = $collection->count();
        $piggyBanks = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($piggyBanks, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.piggy_banks.index') . $this->buildParams());

        /** @var PiggyBankTransformer $transformer */
        $transformer = app(PiggyBankTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($piggyBanks, $transformer, 'piggy_banks');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * List single resource.
     *
     * @param PiggyBank $piggyBank
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function piggyBankEvents(PiggyBank $piggyBank): JsonResponse
    {
        // types to get, page size:
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $manager  = $this->getManager();

        $collection = $this->repository->getEvents($piggyBank);
        $count      = $collection->count();
        $events     = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($events, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.piggy_banks.events', [$piggyBank->id]) . $this->buildParams());

        /** @var PiggyBankEventTransformer $transformer */
        $transformer = app(PiggyBankEventTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($events, $transformer, 'piggy_bank_events');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * List single resource.
     *
     * @param PiggyBank $piggyBank
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function show(PiggyBank $piggyBank): JsonResponse
    {
        $manager = $this->getManager();

        /** @var PiggyBankTransformer $transformer */
        $transformer = app(PiggyBankTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($piggyBank, $transformer, 'piggy_banks');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * Store new object.
     *
     * @param PiggyBankRequest $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function store(PiggyBankRequest $request): JsonResponse
    {
        $piggyBank = $this->repository->store($request->getAll());
        $manager = $this->getManager();

        /** @var PiggyBankTransformer $transformer */
        $transformer = app(PiggyBankTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($piggyBank, $transformer, 'piggy_banks');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update piggy bank.
     *
     * @param PiggyBankRequest $request
     * @param PiggyBank        $piggyBank
     *
     * @return JsonResponse
     */
    public function update(PiggyBankRequest $request, PiggyBank $piggyBank): JsonResponse
    {
        $data      = $request->getAll();
        $piggyBank = $this->repository->update($piggyBank, $data);

        if ('' !== $data['current_amount']) {
            $this->repository->setCurrentAmount($piggyBank, $data['current_amount']);
        }


        $manager = $this->getManager();
        /** @var PiggyBankTransformer $transformer */
        $transformer = app(PiggyBankTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($piggyBank, $transformer, 'piggy_banks');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }
}
