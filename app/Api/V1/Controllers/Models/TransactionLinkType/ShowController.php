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

namespace FireflyIII\Api\V1\Controllers\Models\TransactionLinkType;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\LinkType;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\LinkTypeTransformer;
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
    use TransactionFilter;

    private LinkTypeRepositoryInterface $repository;

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
                $this->repository = app(LinkTypeRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/links/listLinkType
     *
     * @throws FireflyException
     */
    public function index(): JsonResponse
    {
        // create some objects:
        $manager  = $this->getManager();
        $pageSize = $this->parameters->get('limit');

        // get list of accounts. Count it and split it.
        $collection = $this->repository->get();
        $count      = $collection->count();
        $linkTypes  = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($linkTypes, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.link-types.index').$this->buildParams());

        /** @var LinkTypeTransformer $transformer */
        $transformer = app(LinkTypeTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($linkTypes, $transformer, 'link_types');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/links/getLinkType
     *
     * List single resource.
     */
    public function show(LinkType $linkType): JsonResponse
    {
        $manager = $this->getManager();

        /** @var LinkTypeTransformer $transformer */
        $transformer = app(LinkTypeTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($linkType, $transformer, 'link_types');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
