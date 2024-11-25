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

namespace FireflyIII\Api\V1\Controllers\Models\ObjectGroup;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Repositories\ObjectGroup\ObjectGroupRepositoryInterface;
use FireflyIII\Transformers\ObjectGroupTransformer;
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
    private ObjectGroupRepositoryInterface $repository;

    /**
     * ObjectGroupController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user             = auth()->user();
                $this->repository = app(ObjectGroupRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/object_groups/listObjectGroups
     *
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $manager      = $this->getManager();

        // types to get, page size:
        $pageSize     = $this->parameters->get('limit');

        $this->repository->resetOrder();
        $collection   = $this->repository->get();
        $count        = $collection->count();
        $objectGroups = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator    = new LengthAwarePaginator($objectGroups, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.object-groups.index').$this->buildParams());

        /** @var ObjectGroupTransformer $transformer */
        $transformer  = app(ObjectGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource     = new FractalCollection($objectGroups, $transformer, 'object_groups');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/object_groups/getObjectGroup
     *
     * Show single instance.
     */
    public function show(ObjectGroup $objectGroup): JsonResponse
    {
        $manager     = $this->getManager();
        $this->repository->resetOrder();
        $objectGroup->refresh();

        /** @var ObjectGroupTransformer $transformer */
        $transformer = app(ObjectGroupTransformer::class);
        $transformer->setParameters($this->parameters);
        $resource    = new Item($objectGroup, $transformer, 'object_groups');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
