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

namespace FireflyIII\Api\V2\Controllers\Model\TransactionCurrency;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Model\TransactionCurrency\IndexRequest;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Transformers\CurrencyTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexController extends Controller
{
    public const string RESOURCE_KEY                   = 'transaction-currencies';
    protected array                     $acceptedRoles = [UserRoleEnum::READ_ONLY];
    private CurrencyRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(CurrencyRepositoryInterface::class);
                // new way of user group validation
                $userGroup        = $this->validateUserGroup($request);
                $this->repository->setUserGroup($userGroup);

                return $next($request);
            }
        );
    }

    public function index(IndexRequest $request): JsonResponse
    {
        $settings    = $request->getAll();
        if (true === $settings['enabled']) {
            $currencies = $this->repository->get();
        }
        if (true !== $settings['enabled']) {
            $currencies = $this->repository->getAll();
        }

        $pageSize    = $this->parameters->get('limit');
        $count       = $currencies->count();

        // depending on the sort parameters, this list must not be split, because the
        // order is calculated in the account transformer and by that time it's too late.
        $accounts    = $currencies->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);
        $paginator   = new LengthAwarePaginator($accounts, $count, $pageSize, $this->parameters->get('page'));
        $transformer = new CurrencyTransformer();

        $this->parameters->set('pageSize', $pageSize);
        $transformer->setParameters($this->parameters); // give params to transformer

        return response()
            ->json($this->jsonApiList(self::RESOURCE_KEY, $paginator, $transformer))
            ->header('Content-Type', self::CONTENT_TYPE)
        ;
    }
}
