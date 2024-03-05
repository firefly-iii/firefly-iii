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
use FireflyIII\Api\V2\Request\Model\Transaction\InfiniteListRequest;
use FireflyIII\Repositories\UserGroups\Account\AccountRepositoryInterface;
use FireflyIII\Transformers\V2\AccountTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexController extends Controller
{
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
                // new way of user group validation
                $userGroup        = $this->validateUserGroup($request);
                if (null !== $userGroup) {
                    $this->repository->setUserGroup($userGroup);
                }

                return $next($request);
            }
        );
    }

    public function infiniteList(InfiniteListRequest $request): JsonResponse
    {
        $this->repository->resetAccountOrder();

        // get accounts of the specified type, and return.
        $types       = $request->getAccountTypes();

        // get from repository
        $accounts    = $this->repository->getAccountsInOrder($types, $request->getSortInstructions('accounts'), $request->getStartRow(), $request->getEndRow());
        $total       = $this->repository->countAccounts($types);
        $count       = $request->getEndRow() - $request->getStartRow();
        $paginator   = new LengthAwarePaginator($accounts, $total, $count, $this->parameters->get('page'));
        $transformer = new AccountTransformer();
        $transformer->setParameters($this->parameters); // give params to transformer

        return response()
            ->json($this->jsonApiList(self::RESOURCE_KEY, $paginator, $transformer))
            ->header('Content-Type', self::CONTENT_TYPE)
        ;
    }
}
