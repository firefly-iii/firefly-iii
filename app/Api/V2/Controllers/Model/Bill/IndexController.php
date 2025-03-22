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

namespace FireflyIII\Api\V2\Controllers\Model\Bill;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Support\Http\Api\ValidatesUserGroupTrait;
use FireflyIII\Transformers\BillTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class ShowController
 */
class IndexController extends Controller
{
    use ValidatesUserGroupTrait;

    private BillRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(BillRepositoryInterface::class);
                $this->repository->setUserGroup($this->validateUserGroup($request));

                return $next($request);
            }
        );
    }

    /**
     * TODO see autocomplete/accountcontroller for list.
     */
    public function index(): JsonResponse
    {
        $this->repository->correctOrder();
        $bills       = $this->repository->getBills();
        $pageSize    = $this->parameters->get('limit');
        $count       = $bills->count();
        $bills       = $bills->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);
        $paginator   = new LengthAwarePaginator($bills, $count, $pageSize, $this->parameters->get('page'));
        $transformer = new BillTransformer();
        $transformer->setParameters($this->parameters); // give params to transformer

        return response()
            ->json($this->jsonApiList('subscriptions', $paginator, $transformer))
            ->header('Content-Type', self::CONTENT_TYPE)
        ;
    }
}
