<?php

/*
 * IndexController.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V2\Controllers\UserGroup;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Model\Account\IndexRequest;
use FireflyIII\Repositories\UserGroup\UserGroupRepositoryInterface;
use FireflyIII\Transformers\V2\UserGroupTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexController extends Controller
{
    public const string RESOURCE_KEY = 'user_groups';

    private UserGroupRepositoryInterface $repository;

    /**
     * AccountController constructor.
     */
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

    /**
     * TODO see autocomplete/accountcontroller for list.
     */
    public function index(IndexRequest $request): JsonResponse
    {
        $administrations = $this->repository->get();
        $pageSize        = $this->parameters->get('limit');
        $count           = $administrations->count();
        $administrations = $administrations->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);
        $paginator       = new LengthAwarePaginator($administrations, $count, $pageSize, $this->parameters->get('page'));
        $transformer     = new UserGroupTransformer();

        $transformer->setParameters($this->parameters); // give params to transformer

        return response()
            ->json($this->jsonApiList(self::RESOURCE_KEY, $paginator, $transformer))
            ->header('Content-Type', self::CONTENT_TYPE)
        ;
    }
}
