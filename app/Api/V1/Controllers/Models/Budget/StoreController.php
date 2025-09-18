<?php

/*
 * StoreController.php
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

namespace FireflyIII\Api\V1\Controllers\Models\Budget;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\Budget\StoreRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\JsonApi\Enrichments\BudgetEnrichment;
use FireflyIII\Transformers\BudgetTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;

/**
 * Class StoreController
 */
class StoreController extends Controller
{
    private BudgetRepositoryInterface $repository;

    /**
     * StoreController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(BudgetRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/budgets/storeBudget
     *
     * Store a budget.
     *
     * @throws FireflyException
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $data        = $request->getAll();
        $data['fire_webhooks'] ??= true;
        $budget      = $this->repository->store($data);
        $budget->refresh();
        $manager     = $this->getManager();

        // enrich
        /** @var User $admin */
        $admin       = auth()->user();
        $enrichment  = new BudgetEnrichment();
        $enrichment->setUser($admin);
        $budget      = $enrichment->enrichSingle($budget);

        /** @var BudgetTransformer $transformer */
        $transformer = app(BudgetTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new Item($budget, $transformer, 'budgets');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
