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

namespace FireflyIII\Api\V1\Controllers\Models\Bill;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Transformers\BillTransformer;
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
    private BillRepositoryInterface $repository;

    /**
     * BillController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(BillRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function index(): JsonResponse
    {
        $this->repository->correctOrder();
        $bills     = $this->repository->getBills();
        $manager   = $this->getManager();
        $pageSize  = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $count     = $bills->count();
        $bills     = $bills->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);
        $paginator = new LengthAwarePaginator($bills, $count, $pageSize, $this->parameters->get('page'));

        /** @var BillTransformer $transformer */
        $transformer = app(BillTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($bills, $transformer, 'bills');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * Show the specified bill.
     *
     * @param Bill $bill
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function show(Bill $bill): JsonResponse
    {
        $manager = $this->getManager();
        /** @var BillTransformer $transformer */
        $transformer = app(BillTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($bill, $transformer, 'bills');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
