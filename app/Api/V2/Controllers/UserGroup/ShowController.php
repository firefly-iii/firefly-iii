<?php

/*
 * ShowController.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Controllers\UserGroup;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\UserGroup\UserGroupRepositoryInterface;
use FireflyIII\Transformers\UserGroupTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    private UserGroupRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(UserGroupRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    public function index(): JsonResponse
    {
        $collection  = new Collection();
        $pageSize    = $this->parameters->get('limit');
        // if the user has the system owner role, get all. Otherwise, get only the users' groups.
        if (!auth()->user()->hasRole('owner')) {
            $collection = $this->repository->get();
        }
        if (auth()->user()->hasRole('owner')) {
            $collection = $this->repository->getAll();
        }
        $count       = $collection->count();
        $userGroups  = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        $paginator   = new LengthAwarePaginator($userGroups, $count, $pageSize, $this->parameters->get('page'));
        $transformer = new UserGroupTransformer();
        $transformer->setParameters($this->parameters); // give params to transformer

        return response()
            ->json($this->jsonApiList('user-groups', $paginator, $transformer))
            ->header('Content-Type', self::CONTENT_TYPE)
        ;
    }

    public function show(UserGroup $userGroup): JsonResponse
    {
        $transformer = new UserGroupTransformer();
        $transformer->setParameters($this->parameters);

        return response()
            ->api($this->jsonApiObject('user-groups', $userGroup, $transformer))
            ->header('Content-Type', self::CONTENT_TYPE)
        ;
    }
}
