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

namespace FireflyIII\Api\V1\Controllers\Models\Account;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\Account\ShowRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Api\AccountFilter;
use FireflyIII\Support\JsonApi\Enrichments\AccountEnrichment;
use FireflyIII\Transformers\AccountTransformer;
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
    use AccountFilter;

    public const string RESOURCE_KEY = 'accounts';

    private AccountRepositoryInterface $repository;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @throws FireflyException
     */
    public function index(ShowRequest $request): JsonResponse
    {
        $manager     = $this->getManager();
        $params      = $request->getParameters();
        $this->parameters->set('type', $params['type']);

        // types to get, page size:
        $types       = $this->mapAccountTypes($params['type']);

        // get list of accounts. Count it and split it.
        $this->repository->resetAccountOrder();
        $collection  = $this->repository->getAccountsByType($types, $params['sort']);
        $count       = $collection->count();

        // continue sort:
        // TODO if the user sorts on DB dependent field there must be no slice before enrichment, only after.
        // TODO still need to figure out how to do this easily.
        $accounts    = $collection->slice(($this->parameters->get('page') - 1) * $params['limit'], $params['limit']);

        // enrich
        /** @var User $admin */
        $admin       = auth()->user();
        $enrichment  = new AccountEnrichment();
        $enrichment->setSort($params['sort']);
        $enrichment->setDate($this->parameters->get('date'));
        $enrichment->setStart($this->parameters->get('start'));
        $enrichment->setEnd($this->parameters->get('end'));
        $enrichment->setUser($admin);
        $accounts    = $enrichment->enrich($accounts);

        // make paginator:
        $paginator   = new LengthAwarePaginator($accounts, $count, $params['limit'], $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.accounts.index').$this->buildParams());

        /** @var AccountTransformer $transformer */
        $transformer = app(AccountTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new FractalCollection($accounts, $transformer, self::RESOURCE_KEY);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/accounts/getAccount
     *
     * Show single instance.
     */
    public function show(ShowRequest $request, Account $account): JsonResponse
    {
        // get list of accounts. Count it and split it.
        $this->repository->resetAccountOrder();
        $account->refresh();
        $manager     = $this->getManager();

        // enrich
        /** @var User $admin */
        $admin       = auth()->user();
        $enrichment  = new AccountEnrichment();
        $enrichment->setDate($this->parameters->get('date'));
        $enrichment->setStart($this->parameters->get('start'));
        $enrichment->setEnd($this->parameters->get('end'));
        $enrichment->setUser($admin);
        $account     = $enrichment->enrichSingle($account);


        /** @var AccountTransformer $transformer */
        $transformer = app(AccountTransformer::class);
        $transformer->setParameters($this->parameters);
        $resource    = new Item($account, $transformer, self::RESOURCE_KEY);

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
