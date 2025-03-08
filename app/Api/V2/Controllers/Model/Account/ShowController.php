<?php

/*
 * ShowController.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Controllers\Model\Account;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Transformers\V2\AccountTransformer;
use Illuminate\Http\JsonResponse;

/**
 * Show = show a single account.
 * Index = show all accounts
 * Class ShowController
 */
class ShowController extends Controller
{
    public const string RESOURCE_KEY                  = 'accounts';
    protected array                    $acceptedRoles = [UserRoleEnum::READ_ONLY, UserRoleEnum::MANAGE_TRANSACTIONS];
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
                $this->repository->setUserGroup($userGroup);

                return $next($request);
            }
        );
    }

    /**
     * TODO this endpoint is not yet reachable.
     */
    public function show(Account $account): JsonResponse
    {
        $transformer = new AccountTransformer();
        $transformer->setParameters($this->parameters);

        return response()
            ->api($this->jsonApiObject('accounts', $account, $transformer))
            ->header('Content-Type', self::CONTENT_TYPE)
        ;
    }
}
