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

namespace FireflyIII\Api\V1\Controllers\Models\Category;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\Category\ShowRequest;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Support\Http\Api\ChecksSortType;
use FireflyIII\Support\JsonApi\Enrichments\CategoryEnrichment;
use FireflyIII\Transformers\CategoryTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;

/**
 * Class ShowController
 */
final class ShowController extends Controller
{
    use ChecksSortType;
    private CategoryRepositoryInterface $repository;

    /**
     * CategoryController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(function ($request, $next) {
            $this->repository = app(CategoryRepositoryInterface::class);
            $this->repository->setUser(auth()->user());

            return $next($request);
        });
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/categories/listCategory
     *
     * Display a listing of the resource.
     */
    public function index(ShowRequest $request): JsonResponse
    {
        $manager     = $this->getManager();
        [
            'page'   => $page,
            'limit'  => $limit,
            'offset' => $offset,
            'sort'   => $sort,
            'filter' => $filter,
            'start'  => $start,
            'end'    => $end,
        ]            = $request->attributes->all();

        // get list of budgets. Count it and split it.
        $collection  = $this->repository->getCategories($sort, $filter);
        $count       = $collection->count();
        $dbFields    = $this->isAllDatabaseSort($sort, 'Category');
        $categories    = $collection;
        if ($dbFields) {
            $categories = $collection->slice($offset, $limit);
        }


        // enrich
        /** @var User $admin */
        $admin       = auth()->user();
        $enrichment  = new CategoryEnrichment();
        $enrichment->setSort($sort);
        $enrichment->setUser($admin);
        $enrichment->setStart($start);
        $enrichment->setEnd($end);
        $categories  = $enrichment->enrich($categories);

        if (!$dbFields) {
            // now do the slicing.
            $categories = $categories->slice($offset, $limit);
        }

        // make paginator:
        $paginator   = new LengthAwarePaginator($categories, $count, $limit, $page);
        $paginator->setPath(route('api.v1.categories.index').$this->buildParams());

        /** @var CategoryTransformer $transformer */
        $transformer = app(CategoryTransformer::class);
        $resource    = new FractalCollection($categories, $transformer, 'categories');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/categories/getCategory
     *
     * Show the category.
     */
    public function show(Category $category): JsonResponse
    {
        $manager     = $this->getManager();

        /** @var CategoryTransformer $transformer */
        $transformer = app(CategoryTransformer::class);
        $transformer->setParameters($this->parameters);

        // enrich
        /** @var User $admin */
        $admin       = auth()->user();
        $enrichment  = new CategoryEnrichment();
        $enrichment->setUser($admin);
        $enrichment->setStart($this->parameters->get('start'));
        $enrichment->setEnd($this->parameters->get('end'));
        $category    = $enrichment->enrichSingle($category);

        $resource    = new Item($category, $transformer, 'categories');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
