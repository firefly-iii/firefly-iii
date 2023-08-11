<?php

declare(strict_types=1);
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

namespace FireflyIII\Api\V2\Controllers\Model\PiggyBank;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Repositories\Administration\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Transformers\V2\PiggyBankTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    private PiggyBankRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(PiggyBankRepositoryInterface::class);
                $this->repository->setAdministrationId(auth()->user()->user_group_id);
                return $next($request);
            }
        );
    }

    /**
     * @param Request $request
     *
     * TODO see autocomplete/accountcontroller for list.
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $piggies     = $this->repository->getPiggyBanks();
        $pageSize    = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $count       = $piggies->count();
        $piggies     = $piggies->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);
        $paginator   = new LengthAwarePaginator($piggies, $count, $pageSize, $this->parameters->get('page'));
        $transformer = new PiggyBankTransformer();
        $transformer->setParameters($this->parameters); // give params to transformer

        return response()
            ->json($this->jsonApiList('piggy-banks', $paginator, $transformer))
            ->header('Content-Type', self::CONTENT_TYPE);
    }
}
