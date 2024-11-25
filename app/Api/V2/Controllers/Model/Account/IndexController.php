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

namespace FireflyIII\Api\V2\Controllers\Model\Account;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Model\Account\IndexRequest;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Repositories\UserGroups\Account\AccountRepositoryInterface;
use FireflyIII\Transformers\V2\AccountTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class IndexController extends Controller
{
    public const string RESOURCE_KEY                  = 'accounts';

    private AccountRepositoryInterface $repository;
    protected array                    $acceptedRoles = [UserRoleEnum::READ_ONLY, UserRoleEnum::MANAGE_TRANSACTIONS];

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);
                // new way of user group validation
                $userGroup        = $this->validateUserGroup($request);
                $this->repository->setUserGroup($userGroup);

                return $next($request);
            }
        );
    }

    /**
     * TODO the sort instructions need proper repeatable documentation.
     * TODO see autocomplete/account controller for list.
     */
    public function index(IndexRequest $request): JsonResponse
    {
        $this->repository->resetAccountOrder();
        $types             = $request->getAccountTypes();
        $sorting           = $request->getSortInstructions('accounts');
        $filters           = $request->getFilterInstructions('accounts');
        $accounts          = $this->repository->getAccountsByType($types, $sorting, $filters);
        $pageSize          = $this->parameters->get('limit');
        $count             = $accounts->count();

        // depending on the sort parameters, this list must not be split, because the
        // order is calculated in the account transformer and by that time it's too late.
        $first             = array_key_first($sorting);
        $disablePagination = in_array($first, ['last_activity', 'balance', 'balance_difference'], true);
        Log::debug(sprintf('Will disable pagination in account index v2? %s', var_export($disablePagination, true)));
        if (!$disablePagination) {
            $accounts = $accounts->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);
        }
        $paginator         = new LengthAwarePaginator($accounts, $count, $pageSize, $this->parameters->get('page'));
        $transformer       = new AccountTransformer();

        $this->parameters->set('disablePagination', $disablePagination);
        $this->parameters->set('pageSize', $pageSize);
        $this->parameters->set('sort', $sorting);

        $this->parameters->set('filters', $filters);
        $transformer->setParameters($this->parameters); // give params to transformer

        return response()
            ->json($this->jsonApiList('accounts', $paginator, $transformer))
            ->header('Content-Type', self::CONTENT_TYPE)
        ;
    }
}
