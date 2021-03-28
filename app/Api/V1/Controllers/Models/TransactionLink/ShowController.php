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

namespace FireflyIII\Api\V1\Controllers\Models\TransactionLink;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Transformers\TransactionLinkTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;

class ShowController extends Controller
{
    private LinkTypeRepositoryInterface $repository;

    /**
     * TransactionLinkController constructor.
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

                $this->repository = app(LinkTypeRepositoryInterface::class);

                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * List all of the transaction links there are.
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function index(Request $request): JsonResponse
    {
        // create some objects:
        $manager = $this->getManager();
        // read type from URI
        $name = $request->get('name');

        // types to get, page size:
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $linkType = $this->repository->findByName($name);

        // get list of transaction links. Count it and split it.
        $collection   = $this->repository->getJournalLinks($linkType);
        $count        = $collection->count();
        $journalLinks = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($journalLinks, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.transaction_links.index') . $this->buildParams());

        /** @var TransactionLinkTransformer $transformer */
        $transformer = app(TransactionLinkTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($journalLinks, $transformer, 'transaction_links');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);

    }

    /**
     * List single resource.
     *
     * @param TransactionJournalLink $journalLink
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function show(TransactionJournalLink $journalLink): JsonResponse
    {
        $manager = $this->getManager();

        /** @var TransactionLinkTransformer $transformer */
        $transformer = app(TransactionLinkTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($journalLink, $transformer, 'transaction_links');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);

    }
}
