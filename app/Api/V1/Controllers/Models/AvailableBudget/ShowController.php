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

namespace FireflyIII\Api\V1\Controllers\Models\AvailableBudget;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Generic\PaginationDateRangeRequest;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Support\JsonApi\Enrichments\AvailableBudgetEnrichment;
use FireflyIII\Transformers\AvailableBudgetTransformer;
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
    private AvailableBudgetRepositoryInterface $abRepository;

    /**
     * AvailableBudgetController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user               = auth()->user();
                $this->abRepository = app(AvailableBudgetRepositoryInterface::class);
                $this->abRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/available_budgets/getAvailableBudget
     *
     * Display a listing of the resource.
     */
    public function index(PaginationDateRangeRequest $request): JsonResponse
    {
        $manager          = $this->getManager();
        [
            'limit'  => $limit,
            'offset' => $offset,
            'page'   => $page,
            'start'  => $start,
            'end'    => $end,
        ]                 = $request->attributes->all();

        // get list of available budgets. Count it and split it.
        $collection       = $this->abRepository->getAvailableBudgetsByDate($start, $end);
        $count            = $collection->count();
        $availableBudgets = $collection->slice($offset, $limit);

        // enrich
        /** @var User $admin */
        $admin            = auth()->user();
        $enrichment       = new AvailableBudgetEnrichment();
        $enrichment->setUser($admin);
        $availableBudgets = $enrichment->enrich($availableBudgets);

        // make paginator:
        $paginator        = new LengthAwarePaginator($availableBudgets, $count, $limit, $page);
        $paginator->setPath(route('api.v1.available-budgets.index').$this->buildParams());

        /** @var AvailableBudgetTransformer $transformer */
        $transformer      = app(AvailableBudgetTransformer::class);

        $resource         = new FractalCollection($availableBudgets, $transformer, 'available_budgets');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/available_budgets/getAvailableBudget
     *
     * Display the specified resource.
     */
    public function show(AvailableBudget $availableBudget): JsonResponse
    {
        $manager         = $this->getManager();
        //        $start           = $this->parameters->get('start');
        //        $end             = $this->parameters->get('end');

        /** @var AvailableBudgetTransformer $transformer */
        $transformer     = app(AvailableBudgetTransformer::class);

        // enrich
        /** @var User $admin */
        $admin           = auth()->user();
        $enrichment      = new AvailableBudgetEnrichment();
        $enrichment->setUser($admin);
        $availableBudget = $enrichment->enrichSingle($availableBudget);


        $resource        = new Item($availableBudget, $transformer, 'available_budgets');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
